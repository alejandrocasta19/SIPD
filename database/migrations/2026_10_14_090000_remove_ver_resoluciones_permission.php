<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemoveVerResolucionesPermission extends Migration
{
    public function up()
    {
        DB::table('user_permisos')->where('permiso', 'ver_resoluciones')->delete();
    }

    public function down()
    {
    }
}
