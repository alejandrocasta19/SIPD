<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDescargadoEnToCasoDocumentoEstados extends Migration
{
    public function up()
    {
        Schema::table('caso_documento_estados', function (Blueprint $table) {
            $table->timestamp('descargado_en')->nullable()->after('generado_en');
        });

        DB::table('caso_documento_estados')
            ->where('estado', 'generado')
            ->update([
                'descargado_en' => DB::raw('COALESCE(descargado_en, generado_en, updated_at)'),
            ]);
    }

    public function down()
    {
        Schema::table('caso_documento_estados', function (Blueprint $table) {
            $table->dropColumn('descargado_en');
        });
    }
}
