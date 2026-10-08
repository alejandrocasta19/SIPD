<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\RecuperacionContrasena;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->where('role', User::ROLE_EQUIPO)
            ->activos()
            ->first();

        if ($user) {
            $yaPendiente = RecuperacionContrasena::query()
                ->where('user_id', $user->id)
                ->where('estado', RecuperacionContrasena::PENDIENTE)
                ->exists();

            if (!$yaPendiente) {
                DB::transaction(function () use ($user) {
                    RecuperacionContrasena::create([
                        'user_id' => $user->id,
                        'estado' => RecuperacionContrasena::PENDIENTE,
                    ]);

                    Aviso::aCoordinadoras([
                        'remitente_id' => $user->id,
                        'tipo' => Aviso::TIPO_RECUPERACION,
                        'titulo' => 'Solicitud para recuperar contraseña',
                        'cuerpo' => $user->name . ' (' . $user->email . ') necesita restablecer su contraseña.',
                        'motivo' => 'Revisa la solicitud y, si corresponde, envía un enlace seguro para restablecerla.',
                    ]);
                });
            }
        }

        return redirect()
            ->route('password.request')
            ->with('status', 'passwords.sent');
    }

    protected function sendResetLinkResponse(Request $request, $response)
    {
        return redirect()
            ->route('password.request')
            ->with('status', $response);
    }
}
