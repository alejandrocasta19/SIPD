<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermisoMiddleware
{
    public function handle(Request $request, Closure $next, string $permiso)
    {
        $user = $request->user();
        if (!$user || !$user->puede($permiso)) {
            if ($request->expectsJson()) {
                abort(403, 'No tienes permiso para esta acción.');
            }

            return redirect()
                ->route('abogado.dashboard')
                ->with('error', 'La coordinadora no te otorgó permiso para esa función.');
        }

        return $next($request);
    }
}
