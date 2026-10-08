<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Formatos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateCoordinatorCommand extends Command
{
    protected $signature = 'sipd:create-admin {email?} {name?}';

    protected $description = 'Crea de forma interactiva la cuenta inicial de coordinacion';

    public function handle(): int
    {
        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            $this->error('Ya existe una cuenta coordinadora. No se modifico ninguna cuenta.');

            return self::FAILURE;
        }

        $email = trim((string) ($this->argument('email') ?: $this->ask('Correo institucional')));
        $name = trim((string) ($this->argument('name') ?: $this->ask('Nombre de la coordinadora')));
        $password = $this->secret('Contrasena inicial (minimo 12 caracteres)');
        $confirmation = $this->secret('Confirma la contrasena');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => Formatos::reglasNombre(),
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'same:password_confirmation'],
        ], Formatos::mensajes());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'cargo' => 'Coordinadora de RH',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->info('Cuenta coordinadora creada. La contrasena no se ha mostrado ni almacenado en texto plano.');

        return self::SUCCESS;
    }
}
