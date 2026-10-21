<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceivedAtToDisciplinarioAsignaciones extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disciplinario_asignaciones')
            && !Schema::hasColumn('disciplinario_asignaciones', 'received_at')) {
            Schema::table('disciplinario_asignaciones', function (Blueprint $table) {
                $table->timestamp('received_at')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('disciplinario_asignaciones')
            && Schema::hasColumn('disciplinario_asignaciones', 'received_at')) {
            Schema::table('disciplinario_asignaciones', function (Blueprint $table) {
                $table->dropColumn('received_at');
            });
        }
    }
}
