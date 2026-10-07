<?php

namespace App\Http\Middleware;

use App\Models\Aviso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CerrarSesionPorInactividad
{
    public const CLAVE = 'sipd_ultimo_movimiento';

    public const MENSAJE = 'La sesión se cerró por inactividad.';

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $ultimo = $request->session()->get(self::CLAVE);
        $limite = max(1, (int) config('session.idle', 30)) * 60;

        if (is_numeric($ultimo) && (now()->getTimestamp() - (int) $ultimo) >= $limite) {
            return $this->cerrar($request);
        }

        $request->session()->put(self::CLAVE, now()->getTimestamp());

        return $next($request);
    }

    private function cerrar(Request $request)
    {
        Aviso::registrarActividadSesion($request->user(), false);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MENSAJE], 401);
        }

        return redirect()->route('login')->with('status', self::MENSAJE);
    }
}
