<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ForgetRememberCookie
{
    public function handle(Request $request, Closure $next)
    {
        $cookieName = Auth::guard('web')->getRecallerName();

        if ($request->cookies->get($cookieName)) {
            $request->cookies->remove($cookieName);
            Cookie::queue(Cookie::forget($cookieName));
        }

        return $next($request);
    }
}
