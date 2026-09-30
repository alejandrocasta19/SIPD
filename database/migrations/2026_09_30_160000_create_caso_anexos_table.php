<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCasoAnexosTable extends Migration
{
    public function up()
    {
        Schema::create('caso_anexos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('caso_id');
            $table->foreign('caso_id')
                  ->references('id')
                  ->on('disciplinario')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->string('tipo', 32); // archivo_previo | firma_gerente
            $table->string('estado', 32); // cargado | pendiente_firma | firmado
            $table->string('titulo')->nullable();
            $table->text('descripcion')->nullable();

            $table->string('nombre_original');
            $table->string('nombre_almacenado');
            $table->string('extension', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano');
            $table->string('ruta_segura');

            $table->timestamp('firmado_at')->nullable();
            $table->timestamps();

            $table->index(['caso_id', 'tipo']);
            $table->index('estado');
        });
    }

    public function down()
    {
        Schema::dropIfExists('caso_anexos');
    }
}
