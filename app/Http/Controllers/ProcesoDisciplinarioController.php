<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CasoAnexo;
use App\Models\CasoDocumentoEstado;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\ReportService;
use App\Services\OfficialDocumentService;
use Illuminate\Validation\ValidationException;
use App\Support\Paginacion;
use Throwable;

class ProcesoDisciplinarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:registrar_casos')->only(['create', 'plantillaRegistro', 'store']);
        $this->middleware('permiso:ver_casos')->only(['index', 'misCasos', 'show', 'workerSearch', 'downloadSourceDocument']);
        $this->middleware('permiso:editar_casos')->only(['update']);
        $this->middleware('permiso:eliminar_casos')->only(['destroy']);
        $this->middleware('permiso:ver_reportes')->only(['reportes', 'reportesData', 'reportesGlobales', 'exportarCasos']);
        $this->middleware('permiso:ver_reincidencias')->only(['reincidencias']);
        $this->middleware('permiso:ver_plazos')->only(['plazos', 'actualizarDescargosPresentacion']);
        $this->middleware('permiso:ver_resoluciones')->only(['resoluciones']);
    }

    private function visibleProcesses()
    {
        $query = ProcesoDisciplinario::query();

        if (!auth()->user()->esCoordinadora()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    private function accessibleProcess($id)
    {
        return $this->visibleProcesses()->with(['user', 'evidencias', 'anexos'])->findOrFail($id);
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
        $resueltos = $sancionados + $archivados;
        $tasaResolucion = $total > 0 ? (int) round(($resueltos / $total) * 100) : 0;
        $abiertos = $pendientes + $enProceso;

        $pct = function ($count) use ($total) {
            return $total > 0 ? (int) round(($count / $total) * 100) : 0;
        };

        $hora = (int) now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

        $casosAbiertos = (clone $visible)
            ->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->with(['documentoEstados', 'anexos', 'user'])
            ->get()
            ->sortBy(fn ($proceso) => $proceso->diasPlazo())
            ->values();

        $proximoVencer = $casosAbiertos->first();
        $diasVencer = $proximoVencer ? $proximoVencer->diasPlazo() : null;
        $plazosVencidos = $casosAbiertos->filter(fn ($proceso) => $proceso->semaforoPlazo() === 'vencido')->count();
        $plazosPorVencer = $casosAbiertos->filter(fn ($proceso) => $proceso->semaforoPlazo() === 'por_vencer')->count();

        $descargosPendientes = (clone $visible)->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->where(function ($query) {
                $query->whereNull('descargos')->orWhere('descargos', '');
            })
            ->count();

        $alertaDescargos = $casosAbiertos->first(function ($proceso) {
            return !filled($proceso->descargos);
        });

        $alertasActivas = collect([
            $plazosVencidos > 0,
            $plazosPorVencer > 0,
            $descargosPendientes > 0,
        ])->filter()->count();

        $atencion = $casosAbiertos->take(6);

        $recientes = (clone $visible)->with(['user', 'documentoEstados', 'anexos'])
            ->latest()
            ->take(6)
            ->get();

        $anexosTotal = CasoAnexo::whereIn('caso_id', (clone $visible)->select('id'))->count();

        $cargaAbogados = User::where('role', 'abogado')
            ->when(!$user->esCoordinadora(), function ($query) use ($user) {
                $query->whereKey($user->id);
            })
            ->with('permisos')
            ->withCount('procesos')
            ->withCount(['procesos as procesos_abiertos_count' => function ($query) {
                $query->whereIn('estado', ['Pendiente', 'En Proceso']);
            }])
            ->orderByDesc('procesos_count')
            ->take($user->esCoordinadora() ? 12 : 4)
            ->get();

        $pendientesVeredicto = (clone $visible)->where('estado', 'En Proceso')
            ->with('user')
            ->latest()
            ->take(8)
            ->get();

        $solicitudesPendientes = $user->esCoordinadora()
            ? \App\Models\PermisoSolicitud::with('user')->where('estado', 'pendiente')->latest()->take(6)->get()
            : collect();

        $vista = $user->esCoordinadora() ? 'coordinadora.dashboard' : 'abogado.dashboard';

        return view($vista, [
            'pageTitle' => $user->esCoordinadora() ? 'Coordinación de RH' : 'Inicio',
            'user' => $user,
            'saludo' => $saludo,
            'pendientes' => $pendientes,
            'enProceso' => $enProceso,
            'sancionados' => $sancionados,
            'archivados' => $archivados,
            'total' => $total,
            'abiertos' => $abiertos,
            'tasaResolucion' => $tasaResolucion,
            'pctPendiente' => $pct($pendientes),
            'pctProceso' => $pct($enProceso),
            'pctSancionado' => $pct($sancionados),
            'pctArchivado' => $pct($archivados),
            'proximoVencer' => $proximoVencer,
            'diasVencer' => $diasVencer,
            'plazosVencidos' => $plazosVencidos,
            'plazosPorVencer' => $plazosPorVencer,
            'alertaDescargos' => $alertaDescargos,
            'descargosPendientes' => $descargosPendientes,
            'alertasActivas' => $alertasActivas,
            'atencion' => $atencion,
            'recientes' => $recientes,
            'anexosTotal' => $anexosTotal,
            'cargaAbogados' => $cargaAbogados,
            'pendientesVeredicto' => $pendientesVeredicto,
            'solicitudesPendientes' => $solicitudesPendientes,
        ]);
    }

    /**
     * Mostrar formulario
     */
    public function create(OfficialDocumentService $documents)
    {
        $tipoInicial = old('tipo_proceso', 'disciplinario');
        if (!in_array($tipoInicial, CasoDocumentoEstado::TIPOS, true)) {
            $tipoInicial = 'disciplinario';
        }

        $oldBlocks = old('yellow_blocks_' . $tipoInicial, []);

        return view('abogado.Registro', [
            'tipoInicial' => $tipoInicial,
            'interactiveHtml' => $documents->getInteractiveDocumentHtml(
                $tipoInicial,
                is_array($oldBlocks) ? $oldBlocks : [],
                null,
                old('optional_clauses.' . $tipoInicial . '.descargos_fuera_de_termino')
            ),
            'profileUser' => auth()->user(),
        ]);
    }

    public function plantillaRegistro(Request $request, OfficialDocumentService $documents)
    {
        $tipo = (string) $request->query('tipo', '');
        abort_unless(in_array($tipo, CasoDocumentoEstado::TIPOS, true), 404);
        $oldBlocks = old('yellow_blocks_' . $tipo, []);

        return response()->json([
            'html' => $documents->getInteractiveDocumentHtml(
                $tipo,
                is_array($oldBlocks) ? $oldBlocks : [],
                null,
                old('optional_clauses.' . $tipo . '.descargos_fuera_de_termino')
            ),
            'tipo' => $tipo,
            'etiqueta' => CasoDocumentoEstado::etiqueta($tipo),
            'slot' => CasoDocumentoEstado::slotDe($tipo),
            'requiereFirmaGerente' => $documents->requiresGerentePrint($tipo),
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

        $allProcesses = $query->withCount('anexos')->orderBy('created_at', 'desc')->get();

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

        $workersGroupedPaginated = Paginacion::deColeccion($workersGrouped);

        return view('abogado.Reincidencias', [
            'pageTitle'      => 'Reincidencias',
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

        $query = $visible->with('user')->withCount('anexos');

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

        $procesos = Paginacion::deQuery($query->latest());
        $modalidades = $this->visibleProcesses()->whereNotNull('modalidad')
            ->where('modalidad', '!=', '')
            ->distinct()
            ->orderBy('modalidad')
            ->pluck('modalidad');

        return view('abogado.Consultarproceso', [
            'pageTitle' => $mine ? 'Mis casos' : 'Todos los procesos',
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

        $casos = Paginacion::deQuery((clone $query)->whereIn('estado', ['Pendiente', 'En Proceso'])->withCount('anexos')->latest());

        // Historial: cerrados (Sancionado + Archivado)
        $historial = (clone $query)->whereIn('estado', ['Sancionado', 'Archivado'])
            ->with('user')
            ->withCount('anexos')
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
                    'anexos'        => (int) $p->anexos_count,
                ];
            });

        // Stats rápidas del historial
        $hStats = [
            'total'      => $historial->count(),
            'sancionados'=> $historial->where('estado', 'Sancionado')->count(),
            'archivados' => $historial->where('estado', 'Archivado')->count(),
            'prom_dias'  => $historial->whereNotNull('duracion_dias')->avg('duracion_dias'),
            'anexos'     => $historial->sum('anexos'),
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
                ->with('user')
                ->withCount('anexos')
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
            'tipo_proceso' => 'required|in:' . implode(',', CasoDocumentoEstado::TIPOS),
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

        $tipo = $validated['tipo_proceso'];
        $yellowBlocksData = [];
        $bloquesTipo = $request->input('yellow_blocks_' . $tipo, $request->input('yellow_blocks'));
        if (is_array($bloquesTipo)) {
            $yellowBlocksData[$tipo] = array_values($bloquesTipo);
        }

        $slot = CasoDocumentoEstado::slotDe($tipo);
        $slots = [];
        if ($slot) {
            $slots[$slot] = $tipo;
        }

        $optionalClauses = [];
        if ($tipo === 'sancion') {
            $optionalClauses['sancion'] = [
                'descargos_fuera_de_termino' => OfficialDocumentService::normalizeClauseMode(
                    $request->input('optional_clauses.sancion.descargos_fuera_de_termino')
                ),
            ];
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
                'slots' => $slots,
                'optional_clauses' => $optionalClauses,
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

        $proceso->estadoDocumento($tipo)->update([
            'estado' => 'en_diligenciamiento',
        ]);

        return redirect()
            ->route('documentos.hub')
            ->with('success', 'Proceso registrado como borrador. Genera y descarga en Generar documentos.')
            ->with('clear_nuevo_draft', true);
    }

    /**
     * Mostrar detalles de un proceso disciplinario
     */
    public function show($id)
    {
        $proceso = $this->accessibleProcess($id);
        $proceso->loadMissing(['documentoEstados', 'anexos.user']);

        return view('abogado.Detalleproceso', [
            'proceso' => $proceso,
            'pageTitle' => 'PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT),
            'equipoRh' => auth()->user()->esCoordinadora()
                ? User::where('role', 'abogado')->orderBy('name')->get()
                : collect(),
        ]);
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
        ]);

        if ($request->has('descargos_presentacion')) {
            $presentacion = ProcesoDisciplinario::parseDescargosPresentacion(
                $request->input('descargos_presentacion')
            );
            if ($presentacion !== []) {
                $proceso->update($presentacion);
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Proceso actualizado correctamente');
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $proceso = $this->accessibleProcess($id);

        if (in_array($proceso->estado, ['Sancionado', 'Archivado'], true)) {
            return redirect()->back()->with('error', 'Este proceso ya tiene veredicto y no admite cambio de estado.');
        }

        if ($proceso->estado !== 'En Proceso') {
            return redirect()->back()->with('error', 'El veredicto solo se da cuando el caso está En Proceso.');
        }

        $validated = $request->validate([
            'estado' => 'required|in:Sancionado,Archivado',
        ]);

        $proceso->update(['estado' => $validated['estado']]);

        $mensaje = $validated['estado'] === 'Sancionado'
            ? 'Veredicto registrado. El proceso quedó Sancionado.'
            : 'Veredicto registrado. El proceso quedó Archivado.';

        return redirect()->back()->with('success', $mensaje);
    }

    public function solicitarVeredicto($id)
    {
        $proceso = $this->accessibleProcess($id);

        if (in_array($proceso->estado, ['Sancionado', 'Archivado'], true)) {
            return redirect()->back()->with('error', 'Acción denegada: este proceso ya tiene veredicto.');
        }

        if ($proceso->estado === 'En Proceso') {
            return redirect()->back()->with('info', 'Este caso ya está En Proceso, pendiente de veredicto.');
        }

        if (!$proceso->puedeEnviarAProceso()) {
            return redirect()->back()->with('error', 'Genera los 4 documentos oficiales antes de enviar el caso.');
        }

        $proceso->update(['estado' => 'En Proceso']);

        \App\Models\CoordinadoraNotificacion::avisarVeredicto($proceso, auth()->user());

        return redirect()->back()->with('success', 'Caso enviado. Ahora está En Proceso y pendiente de veredicto.');
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

        $resolucionesPaginated = Paginacion::deColeccion($resoluciones);

        return view('abogado.resoluciones', [
            'pageTitle' => 'Resoluciones',
            'resoluciones' => $resolucionesPaginated,
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

        $plazos = $this->visibleProcesses()
            ->latest()
            ->get()
            ->map(function ($proceso) use ($tipos) {
                $tipo = $tipos[$proceso->estado] ?? 'Descargos';
                if ($proceso->estado === 'Pendiente') {
                    $tipo = $proceso->etiquetaTipoDescargos();
                } elseif ($proceso->estado === 'En Proceso' && empty($proceso->decision_final)) {
                    $tipo = 'Audiencia';
                }

                return (object) [
                    'id' => $proceso->id,
                    'proceso_id' => $proceso->id,
                    'conductor' => $proceso->nombre,
                    'tipo' => $tipo,
                    'es_descargos' => $proceso->estado === 'Pendiente',
                    'descargos_presentacion' => $proceso->descargosPresentacionValue(),
                    'vencimiento' => $proceso->vencimientoPlazo(),
                    'dias' => $proceso->diasPlazo(),
                    'semaforo' => $proceso->semaforoPlazo(),
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

        $plazosPaginated = Paginacion::deColeccion($plazos);

        return view('abogado.plazos', [
            'pageTitle' => 'Plazos y términos',
            'plazos' => $plazosPaginated,
            'conteos' => $conteos,
            'filtro' => $filtro,
            'opcionesDescargos' => ProcesoDisciplinario::opcionesDescargosPresentacion(),
        ]);
    }

    public function actualizarDescargosPresentacion(Request $request, $id)
    {
        $proceso = $this->accessibleProcess($id);
        $permitidas = implode(',', array_keys(ProcesoDisciplinario::opcionesDescargosPresentacion()));

        $validated = $request->validate([
            'descargos_presentacion' => 'required|in:' . $permitidas,
        ]);

        $presentacion = ProcesoDisciplinario::parseDescargosPresentacion(
            $validated['descargos_presentacion']
        );

        if ($presentacion !== []) {
            $proceso->update($presentacion);
        }

        return redirect()
            ->route('abogado.plazos', array_filter($request->only('estado')))
            ->with('success', 'Presentación de descargos actualizada.');
    }

    /**
     * VISTA ABOGADOS
     */
    public function abogados()
    {
        $abogados = User::where('role', 'abogado')
            ->with('permisos')
            ->withCount('procesos')
            ->withCount(['procesos as procesos_abiertos_count' => function ($query) {
                $query->whereIn('estado', ['Pendiente', 'En Proceso']);
            }])
            ->orderBy('name')
            ->get();

        return view('coordinadora.abogados', [
            'pageTitle' => 'Equipo y permisos',
            'abogados' => $abogados,
            'catalogoPermisos' => \App\Support\RhPermisos::catalogo(),
            'duracionesPermiso' => \App\Support\RhPermisos::duracionesHoras(),
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

    public function asignarProceso(Request $request, $id)
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $proceso = $this->accessibleProcess($id);
        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $responsable = User::whereKey($validated['user_id'])->where('role', 'abogado')->firstOrFail();
        $proceso->update(['user_id' => $responsable->id]);

        return redirect()->back()->with('success', 'Proceso asignado a ' . $responsable->name . '.');
    }
}