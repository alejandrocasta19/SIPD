<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->eliminarCuentasObsoletas();

        User::updateOrCreate(
            ['email' => 'coordinadora@sipd.co'],
            [
                'name' => 'Coordinadora de RH',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'cargo' => 'Coordinadora de RH',
                'email_verified_at' => now(),
            ]
        );

        $this->upsertEquipo(
            'KELLY JOHANNA RODRIGUEZ VARGAS',
            'Asesora jurídica',
            ['kelly.johanna.rodriguez@pendiente.local']
        );

        $this->upsertEquipo(
            'MARSHALL STEPHEN RINCÓN PEÑA',
            'Jefe de personal'
        );

        $this->upsertEquipo(
            'JORGE CAMILO ROSADO MEJÍA',
            'Asesor jurídico'
        );
    }

    private function eliminarCuentasObsoletas(): void
    {
        User::whereIn('email', ['rh@sipd.co'])->get()->each(function (User $user) {
            $user->delete();
        });
    }

    private function upsertEquipo(string $name, string $cargo, array $emailsAnteriores = []): User
    {
        $email = User::emailInstitucional($name);
        $encontrado = User::where('email', $email)
            ->when($emailsAnteriores !== [], function ($query) use ($emailsAnteriores) {
                $query->orWhereIn('email', $emailsAnteriores);
            })
            ->first();

        $datos = [
            'name' => User::nombreTitulado($name),
            'email' => $email,
            'password' => Hash::make(User::claveInstitucional($name)),
            'role' => User::ROLE_EQUIPO,
            'cargo' => $cargo,
            'email_verified_at' => now(),
        ];

        if ($encontrado) {
            $encontrado->update($datos);

            return $encontrado->fresh();
        }

        return User::create($datos);
    }
}
