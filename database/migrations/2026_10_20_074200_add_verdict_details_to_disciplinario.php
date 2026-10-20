<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->string('veredicto_tipo', 40)->nullable()->after('decision_final');
            $table->unsignedInteger('dias_sancion')->nullable()->after('veredicto_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->dropColumn(['veredicto_tipo', 'dias_sancion']);
        });
    }
};
