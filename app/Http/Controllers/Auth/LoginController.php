<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

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

            case 'coordinadora':
                return '/abogado';

            case 'abogado':
                return '/abogado';

            default:
                return '/';
        }
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