<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(
            'No se crean cuentas por defecto. Crea la primera coordinadora con php artisan sipd:create-admin.'
        );
    }
}
