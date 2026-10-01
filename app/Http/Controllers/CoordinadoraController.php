<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\PermisoSolicitud;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use App\Support\Paginacion;
use App\Support\RhPermisos;
use Illuminate\Http\Request;

class CoordinadoraController extends Controller
{
    public function veredictos()
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $procesos = Paginacion::deQuery(
            ProcesoDisciplinario::with('user')
                ->where('estado', 'En Proceso')
                ->latest('updated_at')
        );

        return view('coordinadora.veredictos', [
            'pageTitle' => 'Veredictos',
            'procesos' => $procesos,
        ]);
    }

    public function solicitudes()
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $pendientes = PermisoSolicitud::with('user')
            ->where('estado', PermisoSolicitud::PENDIENTE)
            ->latest()
            ->get();

        $historial = Paginacion::deQuery(
            PermisoSolicitud::with(['user', 'respondente'])
                ->where('estado', '!=', PermisoSolicitud::PENDIENTE)
                ->latest('responded_at')
        );

        return view('coordinadora.solicitudes', [
            'pageTitle' => 'Solicitudes de permiso',
            'pendientes' => $pendientes,
            'historial' => $historial,
            'duraciones' => RhPermisos::duracionesHoras(),
            'solicitables' => RhPermisos::solicitables(),
        ]);
    }

    public function responderSolicitud(Request $request, $id)
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $solicitud = PermisoSolicitud::with('user')->findOrFail($id);
        abort_unless($solicitud->estaPendiente(), 422, 'Esta solicitud ya fue resuelta.');

        $accion = $request->input('accion');
        abort_unless(in_array($accion, ['otorgar', 'rechazar'], true), 422);

        if ($accion === 'rechazar') {
            $solicitud->update([
                'estado' => PermisoSolicitud::RECHAZADA,
                'responded_by' => auth()->id(),
                'responded_at' => now(),
                'respuesta' => $request->input('respuesta'),
            ]);

            Aviso::enviar($solicitud->user, [
                'remitente_id' => auth()->id(),
                'tipo' => Aviso::TIPO_PERMISO,
                'titulo' => 'Permiso rechazado',
                'cuerpo' => 'No se otorgó ' . $solicitud->etiquetaPermiso() . '.',
                'motivo' => $request->input('respuesta'),
                'solicitud_id' => $solicitud->id,
            ]);

            return back()->with('success', 'Solicitud rechazada.');
        }

        $solicitables = array_keys(RhPermisos::solicitables());
        $validated = $request->validate([
            'permiso' => 'required|in:' . implode(',', $solicitables),
            'duracion' => 'required|in:1,3,5,custom',
            'horas_custom' => 'nullable|integer|min:1|max:168',
            'respuesta' => 'nullable|string|max:500',
        ]);

        $horas = RhPermisos::resolverHoras($validated['duracion'], $validated['horas_custom'] ?? null) ?: $solicitud->horas;

        $solicitud->user->otorgarPermiso($validated['permiso'], $horas, auth()->id());
        $solicitud->update([
            'permiso' => $validated['permiso'],
            'horas' => $horas,
            'estado' => PermisoSolicitud::OTORGADA,
            'responded_by' => auth()->id(),
            'responded_at' => now(),
            'respuesta' => $validated['respuesta'] ?? null,
        ]);

        Aviso::enviar($solicitud->user, [
            'remitente_id' => auth()->id(),
            'tipo' => Aviso::TIPO_PERMISO,
            'titulo' => 'Permiso otorgado',
            'cuerpo' => 'Puedes ' . mb_strtolower(RhPermisos::etiqueta($validated['permiso'])) . ' durante ' . $horas . ' hora' . ($horas === 1 ? '' : 's') . '.',
            'motivo' => $validated['respuesta'] ?? null,
            'solicitud_id' => $solicitud->id,
        ]);

        return back()->with('success', 'Permiso otorgado a ' . $solicitud->user->name . ' por ' . $horas . ' hora(s).');
    }

    public function notificarForm()
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        return view('coordinadora.notificar', [
            'pageTitle' => 'Avisar al equipo',
            'equipo' => User::where('role', 'abogado')->orderBy('name')->get(),
            'procesos' => ProcesoDisciplinario::latest()->take(40)->get(['id', 'nombre', 'estado']),
        ]);
    }

    public function notificarEquipo(Request $request)
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $validated = $request->validate([
            'destinatarios' => 'required|array|min:1',
            'destinatarios.*' => 'integer|exists:users,id',
            'titulo' => 'required|string|max:160',
            'motivo' => 'required|string|max:500',
            'cuerpo' => 'nullable|string|max:2000',
            'proceso_id' => 'nullable|integer|exists:disciplinario,id',
        ]);

        $miembros = User::where('role', 'abogado')
            ->whereIn('id', $validated['destinatarios'])
            ->get();

        foreach ($miembros as $miembro) {
            Aviso::enviar($miembro, [
                'remitente_id' => auth()->id(),
                'tipo' => Aviso::TIPO_AVISO,
                'titulo' => $validated['titulo'],
                'cuerpo' => $validated['cuerpo'] ?? null,
                'motivo' => $validated['motivo'],
                'proceso_id' => $validated['proceso_id'] ?? null,
            ]);
        }

        return redirect()
            ->route('coordinadora.notificar')
            ->with('success', 'Aviso de coordinación enviado a ' . $miembros->count() . ' integrante' . ($miembros->count() === 1 ? '' : 's') . '.');
    }

    public function guardarPermisos(Request $request, $id)
    {
        abort_unless(auth()->user()->esCoordinadora(), 403);

        $miembro = User::where('role', 'abogado')->findOrFail($id);
        $claves = $request->input('permisos', []);
        if (!is_array($claves)) {
            $claves = [];
        }
        $duraciones = $request->input('duracion', []);
        if (!is_array($duraciones)) {
            $duraciones = [];
        }
        $horasCustom = $request->input('horas', []);
        if (!is_array($horasCustom)) {
            $horasCustom = [];
        }

        $miembro->sincronizarPermisos($claves, $duraciones, auth()->id(), $horasCustom);

        return redirect()
            ->route('coordinadora.abogados')
            ->with('success', 'Permisos actualizados para ' . $miembro->name . '.');
    }
}
