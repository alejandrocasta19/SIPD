<?php

use App\Support\Modalidades;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SumarModalidadesExcelKellyJorge extends Migration
{
    public function up()
    {
        $ahora = now();
        $equipo = DB::table('users')->whereIn('role', ['equipo', 'abogado'])->get(['id', 'cargo']);

        foreach ($equipo as $user) {
            if (!in_array(Modalidades::claveCargo($user->cargo), ['asesora juridica', 'asesor juridico'], true)) {
                continue;
            }

            foreach (Modalidades::porDefectoDeCargo($user->cargo) as $permiso) {
                $existe = DB::table('user_permisos')
                    ->where('user_id', $user->id)
                    ->where('permiso', $permiso)
                    ->exists();
                if ($existe) {
                    continue;
                }

                DB::table('user_permisos')->insert([
                    'user_id' => $user->id,
                    'permiso' => $permiso,
                    'expires_at' => null,
                    'granted_by' => null,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down()
    {
        $agregadas = [
            'modalidad_asistente_de_ventas',
            'modalidad_call_center',
            'modalidad_premium_pqr',
            'modalidad_urbanos',
            'modalidad_despacho',
            'modalidad_platino_express_pqr',
            'modalidad_encomiendas',
            'modalidad_mixto',
            'modalidad_doble_yo_pqr',
            'modalidad_platino_jet',
            'modalidad_inspectores_viales',
        ];

        $ids = DB::table('users')->whereIn('role', ['equipo', 'abogado'])->get(['id', 'cargo'])
            ->filter(function ($user) {
                return in_array(Modalidades::claveCargo($user->cargo), ['asesora juridica', 'asesor juridico'], true);
            })
            ->pluck('id');

        DB::table('user_permisos')
            ->whereIn('user_id', $ids)
            ->whereIn('permiso', $agregadas)
            ->delete();
    }
}
