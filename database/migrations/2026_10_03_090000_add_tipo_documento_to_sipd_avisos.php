<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoDocumentoToSipdAvisos extends Migration
{
    public function up()
    {
        Schema::table('sipd_avisos', function (Blueprint $table) {
            $table->string('tipo_documento', 32)->nullable()->after('proceso_id');
        });
    }

    public function down()
    {
        Schema::table('sipd_avisos', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }
}
