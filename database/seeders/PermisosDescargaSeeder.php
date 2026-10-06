<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosDescargaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $ids = DB::table('users')->where('role', 'equipo')->pluck('id');

        foreach ($ids as $uid) {
            DB::table('user_permisos')->updateOrInsert(
                ['user_id' => $uid, 'permiso' => 'descargar_documentos'],
                ['granted_by' => null, 'expires_at' => null,
                 'created_at' => $now, 'updated_at' => $now]
            );
            $name = DB::table('users')->where('id', $uid)->value('name');
            $this->command->info("OK: $name");
        }
    }
}
