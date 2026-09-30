<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfficialCaseFieldsToDisciplinarioTable extends Migration
{
    public function up()
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->string('tipo_proceso')->default('disciplinario')->after('id');
            $table->json('datos_oficiales')->nullable()->after('descripcion_falta');
        });
    }

    public function down()
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_proceso',
                'datos_oficiales',
            ]);
        });
    }
}
