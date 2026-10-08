<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSipdRecuperacionesContrasenaTable extends Migration
{
    public function up()
    {
        Schema::create('sipd_recuperaciones_contrasena', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('estado', 24)->default('pendiente');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
            $table->index(['user_id', 'estado']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sipd_recuperaciones_contrasena');
    }
}
