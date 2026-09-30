<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescargosPresentacionToDisciplinarioTable extends Migration
{
    public function up()
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->string('descargos_forma')->nullable()->after('descargos');
            $table->string('descargos_medio')->nullable()->after('descargos_forma');
        });
    }

    public function down()
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->dropColumn(['descargos_forma', 'descargos_medio']);
        });
    }
}
