<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'coordinadora@sipd.co'],
            [
                'name' => 'Coordinadora de RH',
                'password' => Hash::make('admin123'),
                'role' => 'coordinadora',
                'cargo' => 'Coordinadora de RH',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'rh@sipd.co'],
            [
                'name' => 'Equipo de RH',
                'password' => Hash::make('admin123'),
                'role' => 'abogado',
                'cargo' => 'Equipo de RH',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'kelly.johanna.rodriguez@pendiente.local'],
            [
                'name' => 'KELLY JOHANNA RODRIGUEZ VARGAS',
                'password' => Hash::make('admin123'),
                'role' => 'abogado',
                'cargo' => 'Equipo de RH',
                'email_verified_at' => now(),
            ]
        );
    }
}
