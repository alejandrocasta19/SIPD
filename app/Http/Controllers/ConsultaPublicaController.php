<?php

namespace App\Http\Controllers;

use App\Models\ProcesoDisciplinario;
use Illuminate\Http\Request;

class ConsultaPublicaController extends Controller
{
    public function create()
    {
        $cedula = session('consulta_publica_cedula');

        if (!$cedula) {
            return view('publico.consulta');
        }

        return view('publico.consulta', $this->datosConsulta($cedula));
    }

    public function buscar(Request $request)
    {
        $data = $request->validate([
            'cedula' => ['required', 'string', 'max:30'],
        ]);

        $cedula = preg_replace('/\s+/', '', $data['cedula']);

        $request->session()->put('consulta_publica_cedula', $cedula);

        return redirect()->route('consulta.publica');
    }

    private function datosConsulta(string $cedula): array
    {
        $seguimientos = ProcesoDisciplinario::query()
            ->where('cedula', $cedula)
            ->with([
                'documentoEstados',
                'evidencias:id,caso_id,created_at',
            ])
            ->withCount('evidencias')
            ->latest()
            ->get()
            ->map(fn (ProcesoDisciplinario $proceso) => $proceso->seguimientoPublico());

        return [
            'cedula' => $cedula,
            'seguimientos' => $seguimientos,
            'consultado' => true,
        ];
    }
}
