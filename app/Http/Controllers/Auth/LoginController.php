<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application
    | and redirecting them according to their role.
    |
    */

    use AuthenticatesUsers;

    /**
     * Redirect users according to their role.
     *
     * @return string
     */
    public function redirectTo()
    {
        $role = auth()->user()->role;

        switch ($role) {
            case 'admin':
            case 'equipo':
                return '/abogado';

            default:
                return '/';
        }
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->estaActivo()) {
            Aviso::registrarActividadSesion($user, true);

            return null;
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            $this->username() => 'Tu perfil está desactivado. Consulta con la coordinadora.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            Aviso::registrarActividadSesion($user, false);
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect('/');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}