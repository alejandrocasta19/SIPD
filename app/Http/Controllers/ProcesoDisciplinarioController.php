<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProcesoDisciplinario;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProcesoDisciplinarioController extends Controller
{
    /**
     * Panel de inicio
     */
    public function dashboard()
    {
        $user = auth()->user();

        $pendientes = ProcesoDisciplinario::where('estado', 'Pendiente')->count();
        $enProceso = ProcesoDisciplinario::where('estado', 'En Proceso')->count();
        $sancionados = ProcesoDisciplinario::where('estado', 'Sancionado')->count();
        $archivados = ProcesoDisciplinario::where('estado', 'Archivado')->count();
        $total = ProcesoDisciplinario::count();
        $sinAbogado = ProcesoDisciplinario::whereNull('user_id')->count();
        $resueltos = $sancionados + $archivados;
        $tasaResolucion = $total > 0 ? (int) round(($resueltos / $total) * 100) : 0;

        $pct = function ($count) use ($total) {
            return $total > 0 ? (int) round(($count / $total) * 100) : 0;
        };

        $proximoVencer = ProcesoDisciplinario::whereIn('estado', ['Pendiente', 'En Proceso'])
            ->orderBy('created_at')
            ->first();

        $diasVencer = null;
        if ($proximoVencer) {
            $diasVencer = 15 - now()->startOfDay()->diffInDays($proximoVencer->created_at->copy()->startOfDay());
        }

        $procesosSinAsignar = ProcesoDisciplinario::whereNull('user_id')
            ->latest()
            ->take(4)
            ->get();

        $alertaDescargos = ProcesoDisciplinario::whereIn('estado', ['Pendiente', 'En Proceso'])
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

        $recientes = ProcesoDisciplinario::with('user')
            ->latest()
            ->take(5)
            ->get();

        $cargaAbogados = User::where('role', 'abogado')
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
    public function create()
    {
        return view('abogado.Registro');
    }

    /**
     * Listado de procesos disciplinarios
     */
    public function index(Request $request)
    {
        $conteos = [
            'todos' => ProcesoDisciplinario::count(),
            'Pendiente' => ProcesoDisciplinario::where('estado', 'Pendiente')->count(),
            'En Proceso' => ProcesoDisciplinario::where('estado', 'En Proceso')->count(),
            'Sancionado' => ProcesoDisciplinario::where('estado', 'Sancionado')->count(),
            'Archivado' => ProcesoDisciplinario::where('estado', 'Archivado')->count(),
        ];

        $query = ProcesoDisciplinario::with('user');

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

        $procesos = $query->latest()->paginate(8)->withQueryString();
        $modalidades = ProcesoDisciplinario::whereNotNull('modalidad')
            ->where('modalidad', '!=', '')
            ->distinct()
            ->orderBy('modalidad')
            ->pluck('modalidad');

        return view('abogado.Consultarproceso', [
            'pageTitle' => 'Procesos disciplinarios',
            'procesos' => $procesos,
            'conteos' => $conteos,
            'modalidades' => $modalidades,
        ]);
    }

    /**
     * Guardar proceso disciplinario
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
        ]);

        $rutaDocumento = null;

        // GUARDAR DOCUMENTO
        if ($request->hasFile('documento_falta')) {

            $archivo = $request->file('documento_falta');

            $rutaDocumento = $archivo->store('documentos', 'public');
        }

        ProcesoDisciplinario::create([

            // DATOS DEL CONDUCTOR
            'nombre' => $request->nombre,
            'cedula' => $request->cedula,
            'placa' => $request->placa,
            'ruta' => $request->ruta,
            'modalidad' => $request->modalidad,
            'telefono' => $request->telefono,

            // INFORMACIÓN DISCIPLINARIA
            'tipo_falta' => $request->tipo_falta,
            'descripcion_falta' => $request->descripcion_falta,
            'fecha_falta' => $request->fecha_falta,

            // DOCUMENTO
            'documento_falta' => $rutaDocumento,

            // OBSERVACIONES
            'observacion' => $request->observacion,
            'descargos' => $request->descargos,
            'decision_final' => $request->decision_final,

            // ESTADO
            'estado' => 'Pendiente',

            // USUARIO QUE REGISTRÓ
            'user_id' => auth()->id()
        ]);

        return redirect()
            ->back()
            ->with('success', 'Proceso disciplinario registrado correctamente');
    }

    /**
     * Mostrar detalles de un proceso disciplinario
     */
    public function show($id)
    {
        // CARGAR EL USUARIO RELACIONADO
        $proceso = ProcesoDisciplinario::with('user')->findOrFail($id);

        return view('abogado.Detalleproceso', compact('proceso'));
    }

    /**
     * ACTUALIZAR PROCESO
     */
    public function update(Request $request, $id)
    {
        $proceso = ProcesoDisciplinario::findOrFail($id);

        $rutaDocumento = $proceso->documento_falta;

        // SI SUBE NUEVO DOCUMENTO
        if ($request->hasFile('documento_falta')) {

            $archivo = $request->file('documento_falta');

            $rutaDocumento = $archivo->store('documentos', 'public');
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

            // NUEVO ESTADO
            'estado' => $request->estado
        ]);

        return redirect()
            ->back()
            ->with('success', 'Proceso actualizado correctamente');
    }

    /**
     * Archivo de resoluciones
     */
    public function resoluciones(Request $request)
    {
        $resoluciones = ProcesoDisciplinario::with('user')
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

        return view('abogado.resoluciones', [
            'pageTitle' => 'Resoluciones',
            'resoluciones' => $resoluciones,
            'conteos' => $conteos,
            'filtro' => $filtro,
        ]);
    }

    /**
     * Partes involucradas en los procesos
     */
    public function partes(Request $request)
    {
        $partes = ProcesoDisciplinario::query()
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

        return view('abogado.partes', [
            'pageTitle' => 'Partes involucradas',
            'partes' => $partes,
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

        $plazos = ProcesoDisciplinario::query()
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

        return view('abogado.plazos', [
            'pageTitle' => 'Plazos y términos',
            'plazos' => $plazos,
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
     * DATOS PARA ESTADÍSTICAS
     */
    public function estadisticasDatos(Request $request)
    {
        $query = ProcesoDisciplinario::query();

        // =========================
        // FILTRO POR FECHAS
        // =========================
        if ($request->fecha_desde && $request->fecha_hasta) {

            $query->whereBetween('fecha_falta', [
                $request->fecha_desde,
                $request->fecha_hasta
            ]);
        }

        // =========================
        // FILTRO POR MODALIDAD
        // =========================
        if ($request->filled('modalidad')) {

            $query->where('modalidad', $request->modalidad);
        }

        // =========================
        // CONTADORES
        // =========================

        $archivados = (clone $query)
            ->where('estado', 'Archivado')
            ->count();

        $enProceso = (clone $query)
            ->where('estado', 'En Proceso')
            ->count();

        $pendientes = (clone $query)
            ->where('estado', 'Pendiente')
            ->count();

        $sancionados = (clone $query)
            ->where('estado', 'Sancionado')
            ->count();

        return response()->json([

            'archivados' => $archivados,
            'proceso' => $enProceso,
            'pendientes' => $pendientes,
            'sancionados' => $sancionados

        ]);
    }

    /**
     * ELIMINAR PROCESO
     */
    public function destroy($id)
    {
        $proceso = ProcesoDisciplinario::findOrFail($id);

        $proceso->delete();

        return redirect()
            ->back()
            ->with('success', 'Proceso disciplinario eliminado correctamente');
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