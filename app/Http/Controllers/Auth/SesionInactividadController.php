<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CerrarSesionPorInactividad;
use App\Models\Aviso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SesionInactividadController extends Controller
{
    public function actividad()
    {
        return response()->json(['ok' => true]);
    }

    public function expirar(Request $request)
    {
        Aviso::registrarActividadSesion($request->user(), false);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', CerrarSesionPorInactividad::MENSAJE);
    }
}
