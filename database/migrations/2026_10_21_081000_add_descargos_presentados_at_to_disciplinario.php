<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescargosPresentadosAtToDisciplinario extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disciplinario')
            && !Schema::hasColumn('disciplinario', 'descargos_presentados_at')) {
            Schema::table('disciplinario', function (Blueprint $table) {
                $table->timestamp('descargos_presentados_at')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('disciplinario')
            && Schema::hasColumn('disciplinario', 'descargos_presentados_at')) {
            Schema::table('disciplinario', function (Blueprint $table) {
                $table->dropColumn('descargos_presentados_at');
            });
        }
    }
}
