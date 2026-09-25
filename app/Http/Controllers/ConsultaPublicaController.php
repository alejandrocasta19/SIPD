<?php

namespace App\Http\Controllers;

use App\Models\ProcesoDisciplinario;
use Illuminate\Http\Request;

class ConsultaPublicaController extends Controller
{
    public function create()
    {
        return view('publico.consulta');
    }

    public function buscar(Request $request)
    {
        $data = $request->validate([
            'cedula' => ['required', 'string', 'max:30'],
        ]);

        $cedula = preg_replace('/\s+/', '', $data['cedula']);

        $procesos = ProcesoDisciplinario::query()
            ->where('cedula', $cedula)
            ->latest()
            ->get();

        return view('publico.consulta', [
            'cedula' => $cedula,
            'procesos' => $procesos,
            'consultado' => true,
        ]);
    }
}
