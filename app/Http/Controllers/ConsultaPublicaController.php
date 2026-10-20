<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultaPublicaController extends Controller
{
    public function create(Request $request)
    {
        if ($request->boolean('nueva')) {
            $request->session()->forget([
                'consulta_publica_cedula',
                'consulta_publica_intent',
            ]);

            return redirect()->route('consulta.publica');
        }

        $request->session()->forget('consulta_publica_cedula');

        return view('publico.consulta', [
            'consultaIntentada' => $request->session()->get('consulta_publica_intent', false),
        ]);
    }

    public function buscar(Request $request)
    {
        $validator = Validator::make($request->only('cedula'), [
            'cedula' => ['required', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $cedula = preg_replace('/\D+/', '', $validator->validated()['cedula']) ?? '';

        if (strlen($cedula) < 5 || strlen($cedula) > 15) {
            return back()->withErrors([
                'cedula' => 'Ingresa un número de cédula válido.',
            ]);
        }

        $request->session()->forget('consulta_publica_cedula');
        $request->session()->flash('consulta_publica_intent', true);

        return redirect()->route('consulta.publica');
    }
}
