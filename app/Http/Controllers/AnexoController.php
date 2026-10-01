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
        $this->middleware('permiso:ver_anexos')->only(['index', 'download']);
        $this->middleware('permiso:subir_anexos')->only(['store', 'firmar']);
        $this->middleware('permiso:eliminar_anexos')->only(['destroy']);
    }

    private function visibleProcesses()
    {
        $query = ProcesoDisciplinario::query();
        if (!in_array(auth()->user()->role, ['admin', 'coordinadora'], true)) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    private function visibleAnexos()
    {
        $query = CasoAnexo::query()->with(['caso', 'user']);
        if (!in_array(auth()->user()->role, ['admin', 'coordinadora'], true)) {
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

    public function destroy(int $id)
    {
        $anexo = $this->accessibleAnexo($id);
        $canDelete = auth()->user()->esCoordinadora()
            || (auth()->user()->puede('eliminar_anexos') && $anexo->user_id === auth()->id());
        abort_unless($canDelete, 403, 'No tienes permiso para eliminar este anexo.');

        Storage::disk('local')->delete($anexo->ruta_segura);
        if ($anexo->ruta_firmada && $anexo->ruta_firmada !== $anexo->ruta_segura) {
            Storage::disk('local')->delete($anexo->ruta_firmada);
        }
        $anexo->delete();

        return redirect()->route('abogado.anexos')->with('success', 'Anexo eliminado.');
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
