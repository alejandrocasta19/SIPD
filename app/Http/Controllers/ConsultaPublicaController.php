<?php

namespace App\Http\Controllers;

use App\Models\ProcesoDisciplinario;
use Illuminate\Http\Request;

class ConsultaPublicaController extends Controller
{
    public function create(Request $request)
    {
        if ($request->boolean('nueva')) {
            $request->session()->forget('consulta_publica_cedula');

            return redirect()->route('consulta.publica');
        }

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

        $cedula = ProcesoDisciplinario::normalizarCedula($data['cedula']);

        if (strlen($cedula) < 5 || strlen($cedula) > 15) {
            return back()
                ->withErrors(['cedula' => 'Ingresa un número de cédula válido.'])
                ->withInput();
        }

        $request->session()->put('consulta_publica_cedula', $cedula);

        return redirect()->route('consulta.publica');
    }

    private function datosConsulta(string $cedula): array
    {
        $seguimientos = ProcesoDisciplinario::query()
            ->porCedula($cedula)
            ->with([
                'documentoEstados',
                'evidencias:id,caso_id,created_at',
                'anexos:id,caso_id,created_at',
            ])
            ->withCount(['evidencias', 'anexos'])
            ->latest()
            ->get()
            ->map(fn (ProcesoDisciplinario $proceso) => $proceso->seguimientoPublico());

        return [
            'cedula' => $cedula,
            'cedulaFormato' => ProcesoDisciplinario::formatearCedula($cedula),
            'seguimientos' => $seguimientos,
            'consultado' => true,
        ];
    }
}
