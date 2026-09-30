<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProcesoDisciplinario;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\ReportService;
use App\Services\OfficialDocumentService;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class ProcesoDisciplinarioController extends Controller
{
    private function visibleProcesses()
    {
        $query = ProcesoDisciplinario::query();

        if (!in_array(auth()->user()->role, ['admin', 'coordinadora'], true)) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    private function accessibleProcess($id)
    {
        return $this->visibleProcesses()->with('user')->findOrFail($id);
    }

    /**
     * Panel de inicio
     */
    public function dashboard()
    {
        $user = auth()->user();
        $visible = $this->visibleProcesses();

        $pendientes = (clone $visible)->where('estado', 'Pendiente')->count();
        $enProceso = (clone $visible)->where('estado', 'En Proceso')->count();
        $sancionados = (clone $visible)->where('estado', 'Sancionado')->count();
        $archivados = (clone $visible)->where('estado', 'Archivado')->count();
        $total = (clone $visible)->count();
        $sinAbogado = (clone $visible)->whereNull('user_id')->count();
        $resueltos = $sancionados + $archivados;
        $tasaResolucion = $total > 0 ? (int) round(($resueltos / $total) * 100) : 0;

        $pct = function ($count) use ($total) {
            return $total > 0 ? (int) round(($count / $total) * 100) : 0;
        };

        $proximoVencer = (clone $visible)->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->orderBy('created_at')
            ->first();

        $diasVencer = null;
        if ($proximoVencer) {
            $diasVencer = 15 - now()->startOfDay()->diffInDays($proximoVencer->created_at->copy()->startOfDay());
        }

        $procesosSinAsignar = (clone $visible)->whereNull('user_id')
            ->latest()
            ->take(4)
            ->get();

        $alertaDescargos = (clone $visible)->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->where(function ($query) {
                $query->whereNull('descargos')->orWhere('descargos', '');
            })
            ->oldest()
            ->first();

        $alertasActivas = collect([
            $proximoVencer && $diasVencer !== null && $diasVencer <= 15,
            $sinAbogado > 0,
            $alertaDescargos !== null,
        ])->filter()->count();

        $recientes = (clone $visible)->with('user')
            ->latest()
            ->take(5)
            ->get();

        $cargaAbogados = User::where('role', 'abogado')
            ->when(!in_array($user->role, ['admin', 'coordinadora'], true), function ($query) use ($user) {
                $query->whereKey($user->id);
            })
            ->withCount('procesos')
            ->orderByDesc('procesos_count')
            ->take(4)
            ->get();

        return view('abogado.dashboard', [
            'pageTitle' => 'Inicio',
            'user' => $user,
            'pendientes' => $pendientes,
            'enProceso' => $enProceso,
            'sancionados' => $sancionados,
            'archivados' => $archivados,
            'total' => $total,
            'sinAbogado' => $sinAbogado,
            'tasaResolucion' => $tasaResolucion,
            'pctPendiente' => $pct($pendientes),
            'pctProceso' => $pct($enProceso),
            'pctSancionado' => $pct($sancionados),
            'pctArchivado' => $pct($archivados),
            'proximoVencer' => $proximoVencer,
            'diasVencer' => $diasVencer,
            'procesosSinAsignar' => $procesosSinAsignar,
            'alertaDescargos' => $alertaDescargos,
            'alertasActivas' => $alertasActivas,
            'recientes' => $recientes,
            'cargaAbogados' => $cargaAbogados,
        ]);
    }

    /**
     * Mostrar formulario
     */
    public function create(OfficialDocumentService $documents)
    {
        // En Registro siempre inicia en blanco, salvo si hay 'old' input de alguna validación fallida
        $oldDis = old('yellow_blocks_disciplinario', []);
        $oldCom = old('yellow_blocks_comprobacion', []);
        $oldAct = old('yellow_blocks_acta', []);

        return view('abogado.Registro', [
            'blockDefinitions' => [
                'disciplinario' => $documents->blockDefinitions('disciplinario'),
                'comprobacion'  => $documents->blockDefinitions('comprobacion'),
                'acta'          => $documents->blockDefinitions('acta'),
            ],
            'interactiveHtml' => [
                'disciplinario' => $documents->getInteractiveDocumentHtml('disciplinario', $oldDis),
                'comprobacion'  => $documents->getInteractiveDocumentHtml('comprobacion', $oldCom),
                'acta'          => $documents->getInteractiveDocumentHtml('acta', $oldAct),
            ],
            'profileUser' => auth()->user(),
        ]);
    }

    public function workerSearch(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $workers = $this->visibleProcesses()
            ->where(function ($query) use ($term) {
                $query->where('nombre', 'like', '%' . $term . '%')
                    ->orWhere('cedula', 'like', '%' . $term . '%');
            })
            ->select('nombre', 'cedula', 'modalidad', 'ruta', 'telefono')
            ->orderBy('nombre')
            ->limit(10)
            ->get()
            ->unique(function ($worker) {
                return mb_strtolower((string) $worker->nombre) . '|' . (string) $worker->cedula;
            })
            ->values();

        return response()->json($workers);
    }

    /**
     * Módulo de Consulta y Gestión de Reincidencias
     */
    public function reincidencias(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $query = $this->visibleProcesses();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('cedula', 'like', '%' . $search . '%');
            });
        }

        $allProcesses = $query->orderBy('created_at', 'desc')->get();

        // Agrupar por trabajador (cédula o nombre)
        $workersGrouped = $allProcesses->groupBy(function ($item) {
            return !empty($item->cedula) ? 'CC:' . trim($item->cedula) : 'NAME:' . mb_strtolower(trim($item->nombre));
        })->map(function ($group) {
            $first = $group->first();
            return (object) [
                'nombre'        => $first->nombre,
                'cedula'        => $first->cedula,
                'modalidad'     => $first->modalidad,
                'telefono'      => $first->telefono,
                'placa'         => $first->placa,
                'total_casos'   => $group->count(),
                'es_reincidente'=> $group->count() > 1,
                'procesos'      => $group,
            ];
        })->sortByDesc('total_casos')->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $workersGroupedPaginated = new LengthAwarePaginator(
            $workersGrouped->slice(($page - 1) * $perPage, $perPage)->values(),
            $workersGrouped->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('abogado.Reincidencias', [
            'pageTitle'      => 'Módulo de Reincidencias',
            'workersGrouped' => $workersGroupedPaginated,
            'search'         => $search,
            'profileUser'    => auth()->user(),
        ]);
    }

    /**
     * Listado de procesos disciplinarios
     */
    public function index(Request $request, $mine = false)
    {
        $visible = $this->visibleProcesses();
        $conteos = [
            'todos' => (clone $visible)->count(),
            'Pendiente' => (clone $visible)->where('estado', 'Pendiente')->count(),
            'En Proceso' => (clone $visible)->where('estado', 'En Proceso')->count(),
            'Sancionado' => (clone $visible)->where('estado', 'Sancionado')->count(),
            'Archivado' => (clone $visible)->where('estado', 'Archivado')->count(),
        ];

        $query = $visible->with('user');

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', '%' . $q . '%')
                    ->orWhere('cedula', 'like', '%' . $q . '%')
                    ->orWhere('placa', 'like', '%' . $q . '%')
                    ->orWhere('tipo_falta', 'like', '%' . $q . '%');

                $id = preg_replace('/\D+/', '', $q);
                if ($id !== '') {
                    $sub->orWhere('id', $id);
                }
            });
        }

        if ($request->filled('modalidad')) {
            $query->where('modalidad', $request->modalidad);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $procesos */
        $procesos = $query->latest()->paginate(8);
        $procesos->withQueryString();
        $modalidades = $this->visibleProcesses()->whereNotNull('modalidad')
            ->where('modalidad', '!=', '')
            ->distinct()
            ->orderBy('modalidad')
            ->pluck('modalidad');

        return view('abogado.Consultarproceso', [
            'pageTitle' => $mine ? 'Mis casos' : 'Procesos disciplinarios',
            'procesos' => $procesos,
            'conteos' => $conteos,
            'modalidades' => $modalidades,
            'isMisCasos' => $mine,
        ]);
    }

    public function misCasos(Request $request)
    {
        try {
            return $this->index($request, true);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('abogado.dashboard')
                ->with('error', 'No fue posible cargar tus casos. Intenta nuevamente.');
        }
    }

    public function reportes(Request $request)
    {
        $query = $this->visibleProcesses()->with('user');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', '%' . $q . '%')
                    ->orWhere('cedula', 'like', '%' . $q . '%')
                    ->orWhere('placa', 'like', '%' . $q . '%')
                    ->orWhere('tipo_falta', 'like', '%' . $q . '%');
            });
        }

        if ($request->filled('desde')) {
            $query->where('created_at', '>=', $request->desde . ' 00:00:00');
        }
        if ($request->filled('hasta')) {
            $query->where('created_at', '<=', $request->hasta . ' 23:59:59');
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $casos */
        $casos = (clone $query)->whereIn('estado', ['Pendiente', 'En Proceso'])->latest()->paginate(12);
        $casos->withQueryString();

        // Historial: cerrados (Sancionado + Archivado)
        $historial = (clone $query)->whereIn('estado', ['Sancionado', 'Archivado'])
            ->with('user')
            ->latest('updated_at')
            ->get()
            ->map(function ($p) {
                $inicio  = $p->created_at;
                $cierre  = $p->updated_at ?? $p->created_at;
                $duracion = $inicio ? (int) $inicio->diffInDays($cierre) : null;

                return (object) [
                    'id'            => $p->id,
                    'nombre'        => $p->nombre,
                    'cedula'        => $p->cedula,
                    'placa'         => $p->placa,
                    'tipo_falta'    => $p->tipo_falta,
                    'estado'        => $p->estado,
                    'decision'      => $p->decision_final,
                    'abogado'       => optional($p->user)->name ?? 'Sin asignar',
                    'fecha_inicio'  => $inicio ? $inicio->format('d/m/Y') : '—',
                    'fecha_cierre'  => $cierre ? $cierre->format('d/m/Y') : '—',
                    'duracion_dias' => $duracion,
                    'modalidad'     => $p->modalidad,
                ];
            });

        // Stats rápidas del historial
        $hStats = [
            'total'      => $historial->count(),
            'sancionados'=> $historial->where('estado', 'Sancionado')->count(),
            'archivados' => $historial->where('estado', 'Archivado')->count(),
            'prom_dias'  => $historial->whereNotNull('duracion_dias')->avg('duracion_dias'),
        ];

        return view('abogado.Reportes', [
            'pageTitle' => 'Estadísticas y reportes',
            'casos'     => $casos,
            'historial' => $historial,
            'hStats'    => $hStats,
        ]);
    }

    public function reportesData(Request $request, ReportService $reports)
    {
        try {
            [$from, $to] = $this->reportRange($request);
            
            $query = $this->visibleProcesses();
            if ($request->filled('q')) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', '%' . $q . '%')
                        ->orWhere('cedula', 'like', '%' . $q . '%')
                        ->orWhere('placa', 'like', '%' . $q . '%')
                        ->orWhere('tipo_falta', 'like', '%' . $q . '%');
                });
            }

            return response()->json($reports->statistics(
                $query,
                $from,
                $to
            ));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No fue posible cargar las estadísticas. Intenta nuevamente.',
            ], 500);
        }
    }

    public function reportesGlobales(Request $request, string $format, ReportService $reports)
    {
        try {
            [$from, $to] = $this->reportRange($request);
            
            $query = $this->visibleProcesses();
            if ($request->filled('q')) {
                $q = $request->q;
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', '%' . $q . '%')
                        ->orWhere('cedula', 'like', '%' . $q . '%')
                        ->orWhere('placa', 'like', '%' . $q . '%')
                        ->orWhere('tipo_falta', 'like', '%' . $q . '%');
                });
            }
            
            $data = $reports->statistics($query, $from, $to);

            switch ($format) {
                case 'pdf':
                    return $reports->globalPdf($data);
                case 'word':
                    return $reports->globalWord($data);
                case 'excel':
                    return $reports->globalExcel($data);
                default:
                    abort(404);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No fue posible generar el reporte global.');
        }
    }



    public function exportarCasos(Request $request, string $format, ReportService $reports)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        try {
            $casos = $this->visibleProcesses()
                ->whereIn('id', $validated['ids'])
                ->orderBy('id')
                ->get();

            abort_if($casos->isEmpty(), 404);

            switch ($format) {
                case 'pdf':
                    return $reports->casesPdf($casos);
                case 'word':
                    return $reports->casesWord($casos);
                case 'excel':
                    return $reports->casesExcel($casos);
                default:
                    abort(404);
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No fue posible exportar los casos seleccionados.');
        }
    }

    private function reportRange(Request $request): array
    {
        $validated = $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);

        if (!empty($validated['desde']) && !empty($validated['hasta'])
            && $validated['desde'] > $validated['hasta']) {
            throw ValidationException::withMessages([
                'hasta' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            ]);
        }

        return [$validated['desde'] ?? null, $validated['hasta'] ?? null];
    }

    /**
     * Guardar proceso disciplinario
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_proceso' => 'required|in:disciplinario,comprobacion,acta',
            'nombre'       => 'required|string|max:255',
        ]);

        $rutaDocumento = null;
        $evidenciasToSave = [];
        if ($request->hasFile('documento_falta')) {
            $archivos = $request->file('documento_falta');
            if (!is_array($archivos)) $archivos = [$archivos];

            foreach ($archivos as $archivo) {
                $ext = strtolower($archivo->getClientOriginalExtension());
                abort_unless(in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true), 422, 'Formato de archivo no permitido. Solo se aceptan PDF, JPG, JPEG o PNG.');
                
                $ruta = $archivo->store('documentos', 'public');
                if (!$rutaDocumento) $rutaDocumento = $ruta;
                
                $evidenciasToSave[] = [
                    'user_id'           => auth()->id(),
                    'nombre_original'   => $archivo->getClientOriginalName(),
                    'nombre_almacenado' => basename($ruta),
                    'extension'         => $ext,
                    'mime_type'         => $archivo->getMimeType(),
                    'tamano'            => $archivo->getSize(),
                    'descripcion'       => null,
                    'ruta_segura'       => 'public/' . $ruta,
                ];
            }
        }

        // Recuperar bloques por submódulo o array simple
        $yellowBlocksData = [];

        if ($request->has('yellow_blocks_disciplinario')) {
            $yellowBlocksData['disciplinario'] = array_values($request->input('yellow_blocks_disciplinario', []));
        }
        if ($request->has('yellow_blocks_comprobacion')) {
            $yellowBlocksData['comprobacion'] = array_values($request->input('yellow_blocks_comprobacion', []));
        }
        if ($request->has('yellow_blocks_acta')) {
            $yellowBlocksData['acta'] = array_values($request->input('yellow_blocks_acta', []));
        }

        // Fallback si viene como array genérico yellow_blocks[]
        if (empty($yellowBlocksData) && $request->has('yellow_blocks')) {
            $yellowBlocksData[$validated['tipo_proceso']] = array_values($request->input('yellow_blocks', []));
        }

        $proceso = ProcesoDisciplinario::create([
            'tipo_proceso'      => $validated['tipo_proceso'],
            'nombre'            => $request->nombre,
            'cedula'            => $request->cedula,
            'placa'             => $request->placa,
            'ruta'              => $request->ruta,
            'modalidad'         => $request->modalidad,
            'telefono'          => $request->telefono,
            'tipo_falta'        => $request->tipo_falta,
            'descripcion_falta' => $request->descripcion_falta,
            'datos_oficiales'   => [
                'yellow_blocks' => $yellowBlocksData,
            ],
            'fecha_falta'       => $request->fecha_falta,
            'documento_falta'   => $rutaDocumento,
            'observacion'       => $request->observacion,
            'descargos'         => $request->descargos,
            'decision_final'    => $request->decision_final,
            'estado'            => 'Pendiente',
            'user_id'           => auth()->id(),
        ]);

        foreach ($evidenciasToSave as $ev) {
            $ev['caso_id'] = $proceso->id;
            \App\Models\CasoEvidencia::create($ev);
        }

        return redirect()
            ->route('abogado.detalleproceso', $proceso->id)
            ->with('success', 'Proceso disciplinario registrado correctamente. Descargando el documento oficial...')
            ->with('autodownload', $validated['tipo_proceso']);
    }

    /**
     * Mostrar detalles de un proceso disciplinario
     */
    public function show($id, OfficialDocumentService $documents)
    {
        $proceso = $this->accessibleProcess($id);

        return view('abogado.Detalleproceso', [
            'proceso' => $proceso,
            'officialBlockDefinitions' => $documents->blockDefinitions('acta'),
        ]);
    }

    public function downloadOfficial($id, string $template, OfficialDocumentService $documents)
    {
        try {
            return $documents->downloadOfficial(
                $this->accessibleProcess($id),
                $template,
                $template === 'acta' ? 'acta-cargos-descargos' : 'apertura-' . $template
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())
                ->with('error', 'Completa todos los bloques amarillos antes de generar el documento.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No fue posible generar el documento institucional.');
        }
    }

    public function saveOfficialBlocks(Request $request, $id, string $template, OfficialDocumentService $documents)
    {
        $proceso = $this->accessibleProcess($id);
        
        if ($proceso->estado === 'Sancionado') {
            return redirect()->back()->with('error', 'Acción denegada: Este proceso se encuentra cerrado por Sanción y es inmodificable.');
        }

        $definitions = $documents->blockDefinitions($template);
        $validated = $request->validate([
            'yellow_blocks' => 'required|array|size:' . count($definitions),
            'yellow_blocks.*' => 'required|string|max:30000',
        ]);
        $data = $proceso->datos_oficiales ?: [];
        $data['yellow_blocks'][$template] = array_values($validated['yellow_blocks']);
        $proceso->update(['datos_oficiales' => $data]);

        return redirect()->back()->with('success', 'Información institucional guardada correctamente.');
    }

    public function downloadSourceDocument($id)
    {
        $proceso = $this->accessibleProcess($id);
        abort_unless($proceso->documento_falta, 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return $disk->download($proceso->documento_falta);
    }

    /**
     * ACTUALIZAR PROCESO
     */
    public function update(Request $request, $id)
    {
        $proceso = $this->accessibleProcess($id);

        if ($proceso->estado === 'Sancionado') {
            return redirect()->back()->with('error', 'Acción denegada: Los procesos sancionados están bloqueados permanentemente y no admiten ninguna edición.');
        }

        $rutaDocumento = $proceso->documento_falta;

        // SI SUBE NUEVO DOCUMENTO
        if ($request->hasFile('documento_falta')) {
            $archivos = $request->file('documento_falta');
            if (!is_array($archivos)) $archivos = [$archivos];

            foreach ($archivos as $archivo) {
                $ext = strtolower($archivo->getClientOriginalExtension());
                abort_unless(in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true), 422, 'Formato de archivo no permitido. Solo se aceptan PDF, JPG, JPEG o PNG.');
                
                $ruta = $archivo->store('documentos', 'public');
                $rutaDocumento = $ruta;
                
                \App\Models\CasoEvidencia::create([
                    'caso_id'           => $proceso->id,
                    'user_id'           => auth()->id(),
                    'nombre_original'   => $archivo->getClientOriginalName(),
                    'nombre_almacenado' => basename($ruta),
                    'extension'         => $ext,
                    'mime_type'         => $archivo->getMimeType(),
                    'tamano'            => $archivo->getSize(),
                    'descripcion'       => null,
                    'ruta_segura'       => 'public/' . $ruta,
                ]);
            }
        }

        $proceso->update([

            'nombre' => $request->nombre,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'placa' => $request->placa,
            'ruta' => $request->ruta,
            'modalidad' => $request->modalidad,

            'tipo_falta' => $request->tipo_falta,
            'fecha_falta' => $request->fecha_falta,
            'descripcion_falta' => $request->descripcion_falta,

            'documento_falta' => $rutaDocumento,

            'observacion' => $request->observacion,
            'descargos' => $request->descargos,
            'decision_final' => $request->decision_final,

            'estado' => $request->estado
        ]);

        return redirect()
            ->back()
            ->with('success', 'Proceso actualizado correctamente');
    }

    public function updateStatus(Request $request, $id)
    {
        $proceso = $this->accessibleProcess($id);

        if ($proceso->estado === 'Sancionado') {
            return redirect()->back()->with('error', 'Acción denegada: Los procesos sancionados están bloqueados permanentemente.');
        }

        $validated = $request->validate([
            'estado' => 'required|in:Pendiente,En Proceso,Sancionado,Archivado',
        ]);

        $proceso->update(['estado' => $validated['estado']]);

        return redirect()->back()->with('success', 'Estado del proceso modificado rápidamente.');
    }

    public function solicitarVeredicto($id)
    {
        $proceso = $this->accessibleProcess($id);

        if ($proceso->estado === 'Sancionado') {
            return redirect()->back()->with('error', 'Acción denegada: Este proceso ya fue sancionado.');
        }

        // Se cambia a 'En Proceso' para que el coordinador/admin lo atienda
        $proceso->update(['estado' => 'En Proceso']);

        return redirect()->back()->with('success', 'Información enviada. El caso ahora está "En Proceso" y pendiente de veredicto por el coordinador/admin.');
    }

    /**
     * ELIMINAR PROCESO
     */
    public function destroy($id)
    {
        $proceso = $this->accessibleProcess($id);

        if ($proceso->documento_falta) {
            Storage::disk('public')->delete($proceso->documento_falta);
        }

        $proceso->delete();

        return redirect()
            ->back()
            ->with('success', 'Proceso disciplinario eliminado correctamente');
    }

    /**
     * Archivo de resoluciones
     */
    public function resoluciones(Request $request)
    {
        $resoluciones = $this->visibleProcesses()->with('user')
            ->latest()
            ->get()
            ->map(function ($proceso) {
                $decision = mb_strtolower((string) $proceso->decision_final);

                if (str_contains($decision, 'nulid')) {
                    $tipo = 'nulidad';
                } elseif (str_contains($decision, 'absol')) {
                    $tipo = 'absolutoria';
                } elseif ($proceso->estado === 'Archivado') {
                    $tipo = 'archivo';
                } elseif ($proceso->estado === 'Sancionado' || str_contains($decision, 'sancion')) {
                    $tipo = 'sancionatoria';
                } elseif ($proceso->estado === 'En Proceso') {
                    $tipo = 'sancionatoria';
                } else {
                    $tipo = 'sancionatoria';
                }

                $firmada = filled($proceso->decision_final)
                    || in_array($proceso->estado, ['Sancionado', 'Archivado'], true);

                $fecha = $proceso->updated_at ?: $proceso->created_at;
                $anio = optional($fecha)->format('Y') ?: date('Y');

                return (object) [
                    'id' => $proceso->id,
                    'numero' => $anio . '-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT),
                    'nombre' => $proceso->nombre,
                    'proceso_id' => $proceso->id,
                    'abogado' => $proceso->user->name ?? 'Sin asignar',
                    'expediente' => optional($fecha)->format('Y-m-d'),
                    'tipo' => $tipo,
                    'firmada' => $firmada,
                ];
            });

        $conteos = [
            'todos' => $resoluciones->count(),
            'sancionatoria' => $resoluciones->where('tipo', 'sancionatoria')->count(),
            'absolutoria' => $resoluciones->where('tipo', 'absolutoria')->count(),
            'archivo' => $resoluciones->where('tipo', 'archivo')->count(),
            'nulidad' => $resoluciones->where('tipo', 'nulidad')->count(),
        ];

        $filtro = $request->get('tipo', 'todos');
        if (in_array($filtro, ['sancionatoria', 'absolutoria', 'archivo', 'nulidad'], true)) {
            $resoluciones = $resoluciones->where('tipo', $filtro)->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $resolucionesPaginated = new LengthAwarePaginator(
            $resoluciones->slice(($page - 1) * $perPage, $perPage)->values(),
            $resoluciones->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('abogado.resoluciones', [
            'pageTitle' => 'Resoluciones',
            'resoluciones' => $resolucionesPaginated,
            'conteos' => $conteos,
            'filtro' => $filtro,
        ]);
    }

    /**
     * Partes involucradas en los procesos
     */
    public function partes(Request $request)
    {
        $partes = $this->visibleProcesses()
            ->latest()
            ->get()
            ->map(function ($proceso) {
                return (object) [
                    'id' => $proceso->id,
                    'nombre' => $proceso->nombre,
                    'cedula' => $proceso->cedula,
                    'telefono' => $proceso->telefono,
                    'correo' => null,
                    'rol' => 'investigado',
                    'proceso_id' => $proceso->id,
                    'vinculacion' => $proceso->fecha_falta
                        ? Carbon::parse($proceso->fecha_falta)
                        : $proceso->created_at,
                ];
            });

        $conteos = [
            'todos' => $partes->count(),
            'investigado' => $partes->where('rol', 'investigado')->count(),
            'quejoso' => $partes->where('rol', 'quejoso')->count(),
            'testigo' => $partes->where('rol', 'testigo')->count(),
            'apoderado' => $partes->where('rol', 'apoderado')->count(),
        ];

        $filtro = $request->get('rol', 'todos');
        if (in_array($filtro, ['investigado', 'quejoso', 'testigo', 'apoderado'], true)) {
            $partes = $partes->where('rol', $filtro)->values();
        }

        if ($request->filled('q')) {
            $q = mb_strtolower($request->q);
            $partes = $partes->filter(function ($parte) use ($q) {
                $codigo = 'pro-' . str_pad($parte->proceso_id, 3, '0', STR_PAD_LEFT);
                return str_contains(mb_strtolower((string) $parte->nombre), $q)
                    || str_contains((string) $parte->cedula, $q)
                    || str_contains($codigo, $q)
                    || str_contains((string) $parte->proceso_id, $q);
            })->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $partesPaginated = new LengthAwarePaginator(
            $partes->slice(($page - 1) * $perPage, $perPage)->values(),
            $partes->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('abogado.partes', [
            'pageTitle' => 'Partes involucradas',
            'partes' => $partesPaginated,
            'conteos' => $conteos,
            'filtro' => $filtro,
        ]);
    }

    /**
     * Plazos y términos procesales
     */
    public function plazos(Request $request)
    {
        $tipos = [
            'Pendiente' => 'Descargos',
            'En Proceso' => 'Investigación previa',
            'Sancionado' => 'Resolución sancionatoria',
            'Archivado' => 'Notificación',
        ];

        $hoy = Carbon::now()->startOfDay();

        $plazos = $this->visibleProcesses()
            ->latest()
            ->get()
            ->map(function ($proceso) use ($tipos, $hoy) {
                $inicio = $proceso->created_at
                    ? $proceso->created_at->copy()->startOfDay()
                    : $hoy->copy();

                $vencimiento = $inicio->copy()->addDays(15);
                $dias = $hoy->diffInDays($vencimiento, false);

                if ($dias < 0) {
                    $semaforo = 'vencido';
                } elseif ($dias <= 5) {
                    $semaforo = 'por_vencer';
                } else {
                    $semaforo = 'vigente';
                }

                $tipo = $tipos[$proceso->estado] ?? 'Descargos';
                if ($proceso->estado === 'Pendiente' && empty($proceso->descargos)) {
                    $tipo = 'Descargos';
                } elseif ($proceso->estado === 'Pendiente') {
                    $tipo = 'Investigación previa';
                } elseif ($proceso->estado === 'En Proceso' && empty($proceso->decision_final)) {
                    $tipo = 'Audiencia';
                }

                return (object) [
                    'id' => $proceso->id,
                    'proceso_id' => $proceso->id,
                    'conductor' => $proceso->nombre,
                    'tipo' => $tipo,
                    'vencimiento' => $vencimiento,
                    'dias' => $dias,
                    'semaforo' => $semaforo,
                ];
            });

        $conteos = [
            'todos' => $plazos->count(),
            'vigente' => $plazos->where('semaforo', 'vigente')->count(),
            'por_vencer' => $plazos->where('semaforo', 'por_vencer')->count(),
            'vencido' => $plazos->where('semaforo', 'vencido')->count(),
        ];

        $filtro = $request->get('estado', 'todos');
        if (in_array($filtro, ['vigente', 'por_vencer', 'vencido'], true)) {
            $plazos = $plazos->where('semaforo', $filtro)->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $plazosPaginated = new LengthAwarePaginator(
            $plazos->slice(($page - 1) * $perPage, $perPage)->values(),
            $plazos->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('abogado.plazos', [
            'pageTitle' => 'Plazos y términos',
            'plazos' => $plazosPaginated,
            'conteos' => $conteos,
            'filtro' => $filtro,
        ]);
    }

    /**
     * VISTA ABOGADOS
     */
    public function abogados()
    {
        $abogados = User::where('role', 'abogado')->orderBy('name')->get();

        return view('coordinadora.abogados', [
            'pageTitle' => 'Gestión de RH',
            'abogados' => $abogados,
        ]);
    }





    /**
     * ELIMINAR ABOGADO
     */
    public function eliminarAbogado($id)
    {
        $abogado = User::findOrFail($id);

        $abogado->delete();

        return redirect()
            ->back()
            ->with('success', 'Registro de RH eliminado correctamente');
    }

    /**
     * GUARDAR ABOGADO
     */
    public function guardarAbogado(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'cargo' => 'required'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),

            // ROL DEL SISTEMA
            'role' => 'abogado',

            // CARGO DEL ABOGADO
            'cargo' => $request->cargo
        ]);

        return redirect()
            ->back()
            ->with('success', 'Registro de RH guardado correctamente');
    }

    /**
     * EDITAR ABOGADO
     */
    public function editarAbogado(Request $request, $id)
    {
        $abogado = User::findOrFail($id);

        $abogado->update([
            'name' => $request->name,
            'email' => $request->email,
            'cargo' => $request->cargo
        ]);

        return redirect()
            ->back()
            ->with('success', 'Registro de RH actualizado correctamente');
    }
}