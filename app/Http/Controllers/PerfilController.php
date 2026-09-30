<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function show()
    {
        return redirect()
            ->back()
            ->with('open_profile', true);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user->esCoordinadora() && !$user->puede('editar_perfil')) {
            return back()
                ->with('open_profile', true)
                ->with('error', 'Pide permiso a la coordinadora para editar tu perfil.');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'cedula' => ['nullable', 'string', 'max:30'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return back()
                ->with('open_profile', true)
                ->with('profile_edit', true)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        if (!Schema::hasColumn('users', 'telefono')) {
            unset($data['telefono'], $data['cedula'], $data['fecha_ingreso']);
        }

        $user->update($data);

        return back()->with('profile_success', 'Perfil actualizado correctamente');
    }

    public function password()
    {
        return redirect()
            ->back()
            ->with('open_password', true);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()
                ->with('open_password', true)
                ->withErrors($validator)
                ->withInput();
        }

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->with('open_password', true)
                ->withErrors([
                    'current_password' => 'La contraseña actual no es correcta.',
                ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('password_success', 'Contraseña actualizada correctamente');
    }
}
