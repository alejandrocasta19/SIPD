<?php

namespace App\Services;

use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FirmaDocumentoService
{
    public const TIPOS_FIRMA_COORDINADORA = ['sancion', 'llamado', 'archivo'];
    private const TIPOS_FIRMA_CUENTA = ['comprobacion', 'disciplinario', 'acta'];

    public function puedeCargarFirma(string $tipo, User $user): bool
    {
        if (in_array($tipo, self::TIPOS_FIRMA_CUENTA, true)) {
            return true;
        }

        return in_array($tipo, self::TIPOS_FIRMA_COORDINADORA, true)
            && $user->esCoordinadora();
    }

    public function guardarFirmaCuenta(UploadedFile $archivo, User $user): void
    {
        $ruta = $this->guardarArchivo($archivo, 'firmas/usuarios/' . $user->id);
        $rutaAnterior = $user->firma_path;
        $user->firma_path = $ruta;
        $user->save();

        if ($rutaAnterior && $rutaAnterior !== $ruta) {
            Storage::disk('local')->delete($rutaAnterior);
        }
    }

    public function guardarFirmaTrabajador(UploadedFile $archivo, ProcesoDisciplinario $caso): void
    {
        $ruta = $this->guardarArchivo($archivo, 'firmas/casos/' . $caso->id);
        $datos = $caso->datos_oficiales ?: [];
        $firmas = $datos['firmas_acta'] ?? [];
        $rutaAnterior = $firmas['trabajador'] ?? null;
        $firmas['trabajador'] = $ruta;
        $datos['firmas_acta'] = $firmas;
        $caso->datos_oficiales = $datos;
        $caso->save();

        if ($rutaAnterior && $rutaAnterior !== $ruta) {
            Storage::disk('local')->delete($rutaAnterior);
        }
    }

    /**
     * Returns the signature images in the order in which they appear in each form.
     *
     * @return array<string, string>
     */
    public function rutasParaDocumento(string $tipo, ProcesoDisciplinario $caso, User $usuario): array
    {
        if (in_array($tipo, ['comprobacion', 'disciplinario'], true)) {
            return $this->rutaExistente('cuenta', $usuario->firma_path);
        }

        if ($tipo === 'acta') {
            $firmas = $caso->datos_oficiales['firmas_acta'] ?? [];
            return array_merge(
                $this->rutaExistente('trabajador', $firmas['trabajador'] ?? null),
                $this->rutaExistente('cuenta', $usuario->firma_path)
            );
        }

        if (in_array($tipo, self::TIPOS_FIRMA_COORDINADORA, true)) {
            if (!$usuario->esCoordinadora()) {
                return [];
            }

            return $this->rutaExistente('coordinadora', $usuario->firma_path);
        }

        return [];
    }

    public function usuarioFirmante(string $tipo, User $usuario): User
    {
        if (in_array($tipo, self::TIPOS_FIRMA_COORDINADORA, true) && !$usuario->esCoordinadora()) {
            return $this->coordinadoraFirmante($usuario) ?: $usuario;
        }

        return $usuario;
    }

    private function coordinadoraFirmante(User $usuario): ?User
    {
        if ($usuario->esCoordinadora() && $usuario->estaActivo()) {
            return $usuario;
        }

        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('activo', true)
            ->orderByRaw('firma_path IS NULL')
            ->orderBy('id')
            ->first();
    }

    private function guardarArchivo(UploadedFile $archivo, string $directorio): string
    {
        $extension = match ($archivo->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw new \InvalidArgumentException('La firma debe ser PNG o JPEG.'),
        };
        return $archivo->storeAs($directorio, Str::uuid() . '.' . $extension, 'local');
    }

    private function rutaExistente(string $tipo, ?string $ruta): array
    {
        if (!$ruta || !Storage::disk('local')->exists($ruta)) {
            return [];
        }

        return [$tipo => Storage::disk('local')->path($ruta)];
    }
}
