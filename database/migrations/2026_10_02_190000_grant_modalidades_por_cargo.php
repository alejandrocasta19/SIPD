<?php

use App\Support\Modalidades;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GrantModalidadesPorCargo extends Migration
{
    public function up()
    {
        $now = now();
        $equipo = DB::table('users')->whereIn('role', ['equipo', 'abogado'])->get(['id', 'cargo']);

        foreach ($equipo as $user) {
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
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('user_permisos')
            ->whereIn('permiso', array_keys(Modalidades::permisos()))
            ->delete();
    }
}
