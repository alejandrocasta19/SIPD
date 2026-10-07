<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\PermisoSolicitud;
use App\Support\AlertasSistema;
use App\Support\Paginacion;
use App\Support\RhPermisos;
use Illuminate\Http\Request;

class AvisoController extends Controller
{
    public function index()
    {
        $avisos = Paginacion::deQuery(
            Aviso::query()
                ->where('user_id', auth()->id())
                ->with(['remitente', 'proceso', 'solicitud'])
                ->latest()
        );

        $user = auth()->user();

        return view('notificaciones.index', [
            'pageTitle' => 'Notificaciones',
            'avisos' => $avisos,
            'alertasSistema' => AlertasSistema::visibles($user),
            'puedeBorrar' => $user->puede(RhPermisos::ELIMINAR_NOTIFICACIONES),
        ]);
    }

    public function silenciarAlerta(string $tipo)
    {
        $n = AlertasSistema::silenciar(auth()->user(), $tipo);

        if ($n === 0) {
            return back()->with('info', 'Esa alerta ya no estaba visible.');
        }

        return back()->with('success', 'Alerta quitada. Si entra un caso nuevo de este tipo, vuelve a avisarte.');
    }

    public function silenciarAlertas()
    {
        $n = AlertasSistema::silenciarTodas(auth()->user());

        if ($n === 0) {
            return back()->with('info', 'No hay alertas del sistema para quitar.');
        }

        return back()->with('success', 'Se quitaron las alertas de ahora. Las que se generen después también las podrás borrar.');
    }

    public function leer($id)
    {
        $aviso = Aviso::where('user_id', auth()->id())->findOrFail($id);
        if ($aviso->leida_at === null) {
            $aviso->update(['leida_at' => now()]);
        }

        if ($aviso->solicitud_id && auth()->user()->esCoordinadora()) {
            return redirect()->route('coordinadora.solicitudes');
        }

        if ($aviso->tipo === Aviso::TIPO_VEREDICTO && auth()->user()->esCoordinadora()) {
            return redirect()->route('coordinadora.veredictos');
        }

        if ($aviso->tipo === Aviso::TIPO_FIRMA
            && $aviso->proceso_id
            && $aviso->tipo_documento
            && auth()->user()->esCoordinadora()) {
            return redirect()->route('documentos.edit', [$aviso->proceso_id, $aviso->tipo_documento]);
        }

        if ($aviso->proceso_id) {
            return redirect()->route('abogado.detalleproceso', $aviso->proceso_id);
        }

        return redirect()->route('notificaciones.index');
    }

    public function destroy($id)
    {
        $aviso = Aviso::where('user_id', auth()->id())->findOrFail($id);

        if (!auth()->user()->puede(RhPermisos::ELIMINAR_NOTIFICACIONES)) {
            return back()->with('error', 'Pide a la coordinadora permiso para borrar notificaciones.');
        }

        $aviso->delete();

        return back()->with('success', 'Notificación eliminada.');
    }

    public function destroyLeidas()
    {
        if (!auth()->user()->puede(RhPermisos::ELIMINAR_NOTIFICACIONES)) {
            return back()->with('error', 'Pide a la coordinadora permiso para borrar notificaciones.');
        }

        $borradas = Aviso::where('user_id', auth()->id())
            ->whereNotNull('leida_at')
            ->delete();

        if ($borradas === 0) {
            return back()->with('info', 'No hay notificaciones leídas para borrar.');
        }

        return back()->with('success', 'Se eliminaron las notificaciones leídas.');
    }

    public function solicitar(Request $request)
    {
        abort_if(auth()->user()->esCoordinadora(), 403);

        $solicitables = array_keys(RhPermisos::solicitables());
        $validated = $request->validate([
            'permiso' => 'required|in:' . implode(',', $solicitables),
            'que_hara' => 'required|string|max:1000',
            'motivo' => 'required|string|max:1000',
            'duracion' => 'required|in:1,3,5,custom',
            'horas_custom' => 'nullable|integer|min:1|max:168',
        ]);

        $horas = RhPermisos::resolverHoras($validated['duracion'], $validated['horas_custom'] ?? null);
        if (!$horas) {
            return back()->with('error', 'Indica cuántas horas necesitas.')->withInput();
        }

        $yaPendiente = PermisoSolicitud::where('user_id', auth()->id())
            ->where('permiso', $validated['permiso'])
            ->where('estado', PermisoSolicitud::PENDIENTE)
            ->exists();

        if ($yaPendiente) {
            return back()->with('warning', 'Ya tienes una solicitud pendiente para ese módulo.');
        }

        $solicitud = PermisoSolicitud::create([
            'user_id' => auth()->id(),
            'permiso' => $validated['permiso'],
            'que_hara' => $validated['que_hara'],
            'motivo' => $validated['motivo'],
            'horas' => $horas,
            'estado' => PermisoSolicitud::PENDIENTE,
        ]);

        Aviso::aCoordinadoras([
            'remitente_id' => auth()->id(),
            'tipo' => Aviso::TIPO_SOLICITUD,
            'titulo' => 'Solicitud de permiso',
            'cuerpo' => auth()->user()->name . ' pide ' . RhPermisos::etiqueta($solicitud->permiso) . ' por ' . $horas . ' hora' . ($horas === 1 ? '' : 's') . '.',
            'motivo' => $solicitud->motivo,
            'solicitud_id' => $solicitud->id,
        ]);

        return back()->with('success', 'La solicitud se envió a la coordinadora.');
    }
}
