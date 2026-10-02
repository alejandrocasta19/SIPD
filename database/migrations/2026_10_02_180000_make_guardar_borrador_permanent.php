<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeGuardarBorradorPermanent extends Migration
{
    public function up()
    {
        DB::table('user_permisos')
            ->where('permiso', 'editar_documentos')
            ->update(['expires_at' => null]);
    }

    public function down()
    {
        //
    }
}
