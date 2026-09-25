<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {

            $role = auth()->user()->role;

            switch ($role) {

                case 'admin':
                    return redirect()->route('admin.dashboard');

                case 'abogado':
                    return redirect()->route('abogado.dashboard');

                case 'supervisor':
                    return redirect()->route('supervisor.dashboard');
            }
        }

        return $next($request);
    }
}