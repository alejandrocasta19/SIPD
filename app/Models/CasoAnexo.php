<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasoAnexo extends Model
{
    protected $table = 'caso_anexos';

    public const TIPO_ARCHIVO_PREVIO = 'archivo_previo';
    public const TIPO_FIRMA_GERENTE = 'firma_gerente';

    public const ESTADO_CARGADO = 'cargado';
    public const ESTADO_PENDIENTE_FIRMA = 'pendiente_firma';
    public const ESTADO_FIRMADO = 'firmado';

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    public const ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-word.document.macroenabled.12',
    ];

    public const SCAN_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    public const SCAN_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    protected $fillable = [
        'caso_id',
        'user_id',
        'tipo',
        'estado',
        'titulo',
        'descripcion',
        'nombre_original',
        'nombre_almacenado',
        'extension',
        'mime_type',
        'tamano',
        'ruta_segura',
        'ruta_firmada',
        'nombre_firmado',
        'extension_firmada',
        'mime_firmado',
        'tamano_firmado',
        'firmado_at',
    ];

    protected $casts = [
        'firmado_at' => 'datetime',
    ];

    public static function tipos(): array
    {
        return CasoDocumentoEstado::ETIQUETAS;
    }

    public static function clavesTipo(): array
    {
        return array_keys(self::tipos());
    }

    public static function normalizarTipo(string $tipo): string
    {
        if ($tipo === self::TIPO_FIRMA_GERENTE) {
            return 'terminacion';
        }

        return $tipo;
    }

    public static function requiereEscaneo(string $tipo): bool
    {
        return self::normalizarTipo($tipo) === 'terminacion';
    }

    public static function tiposDelSlot(string $slot): array
    {
        $tipos = CasoDocumentoEstado::variantesDe($slot);
        if ($slot === 'resolucion') {
            $tipos[] = self::TIPO_FIRMA_GERENTE;
        }

        return $tipos;
    }

    public static function resolverTipo(string $tipo): array
    {
        $tipo = self::normalizarTipo($tipo);
        abort_unless(in_array($tipo, self::clavesTipo(), true), 422, 'Selecciona un documento oficial.');

        $esTerminacion = self::requiereEscaneo($tipo);

        return [
            'tipo' => $tipo,
            'estado' => $esTerminacion ? self::ESTADO_FIRMADO : self::ESTADO_CARGADO,
            'titulo' => CasoDocumentoEstado::etiqueta($tipo),
        ];
    }

    public function caso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'caso_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tamanoLegible(?int $bytes = null): string
    {
        $bytes = $bytes ?? (int) $this->tamano;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    public function etiquetaTipo(): string
    {
        $tipo = $this->tipoDocumento();
        if (isset(self::tipos()[$tipo])) {
            return self::tipos()[$tipo];
        }

        return 'Archivo previo';
    }

    public function nombreVisible(): string
    {
        if (isset(self::tipos()[$this->tipoDocumento()])) {
            return $this->etiquetaTipo();
        }

        return $this->titulo ?: $this->nombre_original ?: 'Archivo previo';
    }

    public function etiquetaLugar(): string
    {
        $slot = $this->slot();

        return CasoDocumentoEstado::SLOT_LABELS[$slot] ?? 'Archivo previo';
    }

    public function tipoDocumento(): string
    {
        return self::normalizarTipo((string) $this->tipo);
    }

    public function slot(): ?string
    {
        return CasoDocumentoEstado::slotDe($this->tipoDocumento());
    }

    public function etiquetaEstado(): string
    {
        if ($this->estado === self::ESTADO_PENDIENTE_FIRMA) {
            return 'Pendiente de firma';
        }
        if ($this->estado === self::ESTADO_FIRMADO) {
            return 'Firmado';
        }
        return 'En archivo';
    }

    public function requiereFirma(): bool
    {
        return self::requiereEscaneo((string) $this->tipo)
            && $this->estado === self::ESTADO_PENDIENTE_FIRMA;
    }

    public function rutaDescarga(): ?string
    {
        if ($this->estado === self::ESTADO_FIRMADO && $this->ruta_firmada) {
            return $this->ruta_firmada;
        }

        return $this->ruta_segura;
    }

    public function nombreDescarga(): string
    {
        if ($this->estado === self::ESTADO_FIRMADO && $this->nombre_firmado) {
            return $this->nombre_firmado;
        }

        return (string) $this->nombre_original;
    }

    public function extensionVigente(): string
    {
        if ($this->estado === self::ESTADO_FIRMADO && $this->extension_firmada) {
            return $this->extension_firmada;
        }

        return (string) $this->extension;
    }

    public function tamanoVigente(): string
    {
        $bytes = $this->estado === self::ESTADO_FIRMADO && $this->tamano_firmado
            ? (int) $this->tamano_firmado
            : (int) $this->tamano;

        return $this->tamanoLegible($bytes);
    }
}
