<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            // Número interno del vehículo (solo aplica a modalidades con placa)
            $table->string('numero_interno', 50)->nullable()->after('placa');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinario', function (Blueprint $table) {
            $table->dropColumn('numero_interno');
        });
    }
};
