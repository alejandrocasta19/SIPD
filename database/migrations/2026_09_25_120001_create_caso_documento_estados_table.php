<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCasoDocumentoEstadosTable extends Migration
{
    public function up()
    {
        Schema::create('caso_documento_estados', function (Blueprint $table) {
            $table->id();

            // CASO Y TIPO DE DOCUMENTO
            $table->unsignedBigInteger('caso_id');
            $table->foreign('caso_id')
                  ->references('id')
                  ->on('disciplinario')
                  ->onDelete('cascade');

            // disciplinario | comprobacion | acta
            $table->string('tipo_documento', 30);

            // no_iniciado | en_diligenciamiento | completo | generado
            $table->string('estado', 30)->default('no_iniciado');

            // Fecha de última generación
            $table->timestamp('generado_en')->nullable();

            // Par único por caso+tipo
            $table->unique(['caso_id', 'tipo_documento']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('caso_documento_estados');
    }
}
