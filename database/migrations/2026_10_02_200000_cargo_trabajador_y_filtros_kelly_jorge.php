<?php

use App\Support\Modalidades;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CargoTrabajadorYFiltrosKellyJorge extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('disciplinario', 'cargo')) {
            Schema::table('disciplinario', function (Blueprint $table) {
                $table->string('cargo')->nullable()->after('modalidad');
            });
        }

        $ahora = now();
        $equipo = DB::table('users')->whereIn('role', ['equipo', 'abogado'])->get(['id', 'cargo']);
        foreach ($equipo as $user) {
            $claves = Modalidades::porDefectoDeCargo($user->cargo);
            if (!in_array(Modalidades::claveCargo($user->cargo), ['asesora juridica', 'asesor juridico'], true)) {
                continue;
            }

            DB::table('user_permisos')
                ->where('user_id', $user->id)
                ->where('permiso', 'like', 'modalidad_%')
                ->delete();

            foreach ($claves as $permiso) {
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
        if (Schema::hasColumn('disciplinario', 'cargo')) {
            Schema::table('disciplinario', function (Blueprint $table) {
                $table->dropColumn('cargo');
            });
        }
    }
}
