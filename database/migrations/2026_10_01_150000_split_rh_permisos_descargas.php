<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SplitRhPermisosDescargas extends Migration
{
    public function up()
    {
        $now = now();
        $grants = DB::table('user_permisos')->get()->groupBy('user_id');

        foreach ($grants as $userId => $rows) {
            $claves = $rows->pluck('permiso')->all();
            $nuevos = [];

            if (in_array('ver_documentos', $claves, true) || in_array('editar_documentos', $claves, true)) {
                $nuevos[] = 'generar_documentos';
                $nuevos[] = 'descargar_documentos';
            }
            if (in_array('ver_anexos', $claves, true)) {
                $nuevos[] = 'descargar_anexos';
            }
            if (in_array('ver_reportes', $claves, true)) {
                $nuevos[] = 'exportar_reportes';
            }

            foreach (array_unique($nuevos) as $permiso) {
                if (in_array($permiso, $claves, true)) {
                    continue;
                }

                DB::table('user_permisos')->insert([
                    'user_id' => $userId,
                    'permiso' => $permiso,
                    'expires_at' => null,
                    'granted_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('user_permisos')->whereIn('permiso', [
            'generar_documentos',
            'descargar_documentos',
            'descargar_anexos',
            'exportar_reportes',
        ])->delete();
    }
}
