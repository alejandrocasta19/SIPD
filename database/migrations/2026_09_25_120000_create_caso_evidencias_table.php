<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCasoEvidenciasTable extends Migration
{
    public function up()
    {
        Schema::create('caso_evidencias', function (Blueprint $table) {
            $table->id();

            // RELACIÓN CON EL CASO
            $table->unsignedBigInteger('caso_id');
            $table->foreign('caso_id')
                  ->references('id')
                  ->on('disciplinario')
                  ->onDelete('cascade');

            // USUARIO QUE CARGÓ
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // METADATOS DEL ARCHIVO
            $table->string('nombre_original');
            $table->string('nombre_almacenado');
            $table->string('extension', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano');       // bytes
            $table->text('descripcion')->nullable();
            $table->string('ruta_segura');              // relativa a storage/app/evidencias

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('caso_evidencias');
    }
}
