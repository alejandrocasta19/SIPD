<?php

namespace App\Http\Controllers;

use App\Models\CasoAnexo;
use App\Models\CasoDocumentoEstado;
use App\Models\ProcesoDisciplinario;
use App\Support\Paginacion;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AnexoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:ver_anexos')->only(['index']);
        $this->middleware('permiso:descargar_anexos')->only(['download']);
        $this->middleware('permiso:subir_anexos')->only(['store', 'firmar']);
        $this->middleware('permiso:editar_anexos')->only(['update']);
        $this->middleware('permiso:eliminar_anexos')->only(['destroy']);
    }

    private function visibleProcesses()
    {
        $query = ProcesoDisciplinario::query();
        if (!auth()->user()->esCoordinadora()) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    private function visibleAnexos()
    {
        $query = CasoAnexo::query()->with(['caso', 'user']);
        if (!auth()->user()->esCoordinadora()) {
            $query->whereHas('caso', function ($sub) {
                $sub->where('user_id', auth()->id());
            });
        }
        return $query;
    }

    private function accessibleAnexo(int $id): CasoAnexo
    {
        return $this->visibleAnexos()->findOrFail($id);
    }

    public function index(Request $request)
    {
        $query = $this->visibleAnexos()->latest();
        $filtro = $request->get('filtro', 'todos');

        if (isset(CasoDocumentoEstado::SLOTS[$filtro])) {
            $query->whereIn('tipo', CasoAnexo::tiposDelSlot($filtro));
        } elseif ($filtro === 'pendiente') {
            $query->where('estado', CasoAnexo::ESTADO_PENDIENTE_FIRMA);
        } elseif ($filtro === 'firmado') {
            $query->where(function ($sub) {
                $sub->where('estado', CasoAnexo::ESTADO_FIRMADO)
                    ->orWhereIn('tipo', ['terminacion', CasoAnexo::TIPO_FIRMA_GERENTE]);
            });
        } elseif ($filtro === 'existente') {
            $query->where('tipo', CasoAnexo::TIPO_ARCHIVO_PREVIO);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titulo', 'like', '%' . $q . '%')
                    ->orWhere('nombre_original', 'like', '%' . $q . '%')
                    ->orWhere('nombre_firmado', 'like', '%' . $q . '%')
                    ->orWhereHas('caso', function ($caso) use ($q) {
                        $caso->where('nombre', 'like', '%' . $q . '%')
                            ->orWhere('cedula', 'like', '%' . $q . '%');
                        $id = preg_replace('/\D+/', '', $q);
                        if ($id !== '') {
                            $caso->orWhere('id', $id);
                        }
                    });
            });
        }

        $base = $this->visibleAnexos();
        $conteos = [
            'todos' => (clone $base)->count(),
            'existente' => (clone $base)->where('tipo', CasoAnexo::TIPO_ARCHIVO_PREVIO)->count(),
            'pendiente' => (clone $base)->where('estado', CasoAnexo::ESTADO_PENDIENTE_FIRMA)->count(),
            'firmado' => (clone $base)->where(function ($sub) {
                $sub->where('estado', CasoAnexo::ESTADO_FIRMADO)
                    ->orWhereIn('tipo', ['terminacion', CasoAnexo::TIPO_FIRMA_GERENTE]);
            })->count(),
        ];
        foreach (array_keys(CasoDocumentoEstado::SLOTS) as $slot) {
            $conteos[$slot] = (clone $base)->whereIn('tipo', CasoAnexo::tiposDelSlot($slot))->count();
        }

        return view('abogado.anexos', [
            'pageTitle' => 'Anexos escaneados',
            'anexos' => Paginacion::deQuery($query),
            'casos' => $this->visibleProcesses()->latest()->get(['id', 'nombre', 'cedula', 'estado']),
            'conteos' => $conteos,
            'filtro' => $filtro,
            'casoSeleccionado' => $request->get('caso', old('caso_id')),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'caso_id' => 'required|integer',
            'tipo' => 'required|in:' . implode(',', array_merge(CasoAnexo::clavesTipo(), [CasoAnexo::TIPO_FIRMA_GERENTE])),
            'archivo' => ['required', 'file', 'max:10240'],
        ]);

        $caso = $this->visibleProcesses()->findOrFail($validated['caso_id']);
        if ($respuesta = $caso->redireccionSiBloqueado()) {
            return $respuesta;
        }
        $resuelto = CasoAnexo::resolverTipo($validated['tipo']);
        $esTerminacion = CasoAnexo::requiereEscaneo($resuelto['tipo']);
        $meta = $this->storeFile(
            $request->file('archivo'),
            $caso->id,
            $esTerminacion ? 'tc' : 'ax',
            $esTerminacion ? CasoAnexo::SCAN_EXTENSIONS : CasoAnexo::ALLOWED_EXTENSIONS,
            $esTerminacion ? CasoAnexo::SCAN_MIMES : CasoAnexo::ALLOWED_MIMES,
            $esTerminacion
                ? 'El escaneo de la terminación firmada debe ser PDF, JPG o PNG.'
                : 'Solo se aceptan documentos Word o PDF.'
        );

        CasoAnexo::create(array_merge($meta, [
            'caso_id' => $caso->id,
            'user_id' => auth()->id(),
            'tipo' => $resuelto['tipo'],
            'estado' => $resuelto['estado'],
            'titulo' => $resuelto['titulo'],
            'firmado_at' => $esTerminacion ? now() : null,
        ]));

        $slot = CasoDocumentoEstado::slotDe($resuelto['tipo']);
        $mensaje = $esTerminacion
            ? 'Se archivó el escaneo firmado en ' . $resuelto['titulo'] . '.'
            : 'El documento quedó en ' . $resuelto['titulo'] . '.';

        return redirect()
            ->route('abogado.anexos', array_filter([
                'filtro' => $slot,
                'caso' => $request->get('caso'),
            ]))
            ->with('success', $mensaje);
    }

    public function download(int $id)
    {
        $anexo = $this->accessibleAnexo($id);
        $relativa = $anexo->rutaDescarga();
        abort_unless($relativa, 404);

        $ruta = storage_path('app/' . $relativa);
        abort_unless(is_file($ruta), 404);

        return response()->download($ruta, $anexo->nombreDescarga());
    }

    public function firmar(Request $request, int $id)
    {
        $anexo = $this->accessibleAnexo($id);
        if ($respuesta = optional($anexo->caso)->redireccionSiBloqueado()) {
            return $respuesta;
        }
        abort_unless(
            CasoAnexo::requiereEscaneo((string) $anexo->tipo),
            422,
            'Solo la terminación por justas causas recibe el escaneo firmado por gerencia.'
        );

        $request->validate([
            'archivo' => ['required', 'file', 'max:10240'],
        ]);

        $meta = $this->storeFile(
            $request->file('archivo'),
            $anexo->caso_id,
            'sg',
            CasoAnexo::SCAN_EXTENSIONS,
            CasoAnexo::SCAN_MIMES,
            'El escaneo firmado debe ser PDF, JPG o PNG.'
        );

        $anteriores = array_filter([
            $anexo->ruta_segura,
            $anexo->ruta_firmada,
        ]);

        $anexo->update([
            'ruta_segura' => $meta['ruta_segura'],
            'nombre_original' => $meta['nombre_original'],
            'nombre_almacenado' => $meta['nombre_almacenado'],
            'extension' => $meta['extension'],
            'mime_type' => $meta['mime_type'],
            'tamano' => $meta['tamano'],
            'ruta_firmada' => null,
            'nombre_firmado' => null,
            'extension_firmada' => null,
            'mime_firmado' => null,
            'tamano_firmado' => null,
            'estado' => CasoAnexo::ESTADO_FIRMADO,
            'firmado_at' => now(),
        ]);

        foreach ($anteriores as $ruta) {
            if ($ruta !== $meta['ruta_segura']) {
                Storage::disk('local')->delete($ruta);
            }
        }

        return redirect()
            ->route('abogado.anexos', ['filtro' => 'firmado'])
            ->with('success', 'Se archivó el escaneo firmado. Ese es el documento del expediente.');
    }

    public function update(Request $request, int $id)
    {
        $anexo = $this->accessibleAnexo($id);
        $validated = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_merge(CasoAnexo::clavesTipo(), [CasoAnexo::TIPO_FIRMA_GERENTE])),
            'archivo' => ['nullable', 'file', 'max:10240'],
        ]);

        $resuelto = CasoAnexo::resolverTipo($validated['tipo']);
        $esTerminacion = CasoAnexo::requiereEscaneo($resuelto['tipo']);
        $extensiones = $esTerminacion ? CasoAnexo::SCAN_EXTENSIONS : CasoAnexo::ALLOWED_EXTENSIONS;
        $mimes = $esTerminacion ? CasoAnexo::SCAN_MIMES : CasoAnexo::ALLOWED_MIMES;
        $mensaje = $esTerminacion
            ? 'El escaneo de la terminación firmada debe ser PDF, JPG o PNG.'
            : 'Solo se aceptan documentos Word o PDF.';

        $payload = [
            'tipo' => $resuelto['tipo'],
            'titulo' => $resuelto['titulo'],
            'estado' => $resuelto['estado'],
        ];

        $archivo = $request->file('archivo');
        if ($archivo) {
            $meta = $this->storeFile(
                $archivo,
                $anexo->caso_id,
                $esTerminacion ? 'tc' : 'ax',
                $extensiones,
                $mimes,
                $mensaje
            );
            $this->borrarArchivos($anexo, $meta['ruta_segura']);
            $payload = array_merge($payload, $meta, [
                'ruta_firmada' => null,
                'nombre_firmado' => null,
                'extension_firmada' => null,
                'mime_firmado' => null,
                'tamano_firmado' => null,
            ]);
        } else {
            abort_unless(
                in_array(strtolower((string) $anexo->extension), $extensiones, true),
                422,
                $mensaje
            );
        }

        if ($esTerminacion) {
            $payload['firmado_at'] = $anexo->firmado_at ?: now();
        } else {
            $payload['firmado_at'] = null;
            $payload['ruta_firmada'] = null;
            $payload['nombre_firmado'] = null;
            $payload['extension_firmada'] = null;
            $payload['mime_firmado'] = null;
            $payload['tamano_firmado'] = null;
        }

        $anexo->update($payload);

        return redirect()
            ->route('abogado.anexos', array_filter([
                'filtro' => CasoDocumentoEstado::slotDe($resuelto['tipo']),
                'caso' => $request->get('caso'),
            ]))
            ->with('success', 'Se actualizó el anexo de ' . $resuelto['titulo'] . '.');
    }

    public function destroy(int $id)
    {
        try {
            $anexo = $this->accessibleAnexo($id);
            abort_unless($this->puedeBorrarAnexo($anexo), 403, 'No tienes permiso para eliminar este anexo.');

            $this->borrarArchivos($anexo);
            $anexo->delete();

            return redirect()->route('abogado.anexos')->with('success', 'Anexo eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar el anexo: ' . $e->getMessage());
        }
    }

    private function puedeBorrarAnexo(CasoAnexo $anexo): bool
    {
        $user = auth()->user();
        if ($user->esCoordinadora()) {
            return true;
        }
        if (!$user->puede('eliminar_anexos')) {
            return false;
        }

        return (int) $anexo->user_id === (int) $user->id
            || (int) optional($anexo->caso)->user_id === (int) $user->id;
    }

    private function borrarArchivos(CasoAnexo $anexo, ?string $excepto = null): void
    {
        foreach (array_unique(array_filter([$anexo->ruta_segura, $anexo->ruta_firmada])) as $ruta) {
            if ($ruta !== $excepto) {
                Storage::disk('local')->delete($ruta);
            }
        }
    }

    private function storeFile(
        UploadedFile $archivo,
        int $casoId,
        string $prefijo,
        array $extensiones,
        array $mimes,
        string $mensaje
    ): array {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $mime = strtolower((string) $archivo->getMimeType());

        $mimeOk = in_array($mime, $mimes, true)
            || (
                in_array($extension, ['doc', 'docx'], true)
                && in_array($mime, ['application/octet-stream', 'application/zip'], true)
            );

        abort_unless(in_array($extension, $extensiones, true) && $mimeOk, 422, $mensaje);

        $nombreAlmacenado = $prefijo . '_' . $casoId . '_' . uniqid() . '.' . $extension;
        $ruta = $archivo->storeAs('anexos/' . $casoId, $nombreAlmacenado, 'local');

        return [
            'nombre_original' => $archivo->getClientOriginalName(),
            'nombre_almacenado' => $nombreAlmacenado,
            'extension' => $extension,
            'mime_type' => $mime,
            'tamano' => $archivo->getSize(),
            'ruta_segura' => $ruta,
        ];
    }
}
