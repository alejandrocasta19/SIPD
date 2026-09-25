<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatedisciplinarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinario', function (Blueprint $table) {

            $table->id();

            // DATOS DEL CONDUCTOR
            $table->string('nombre');
            $table->string('cedula')->nullable();
            $table->string('placa')->nullable();
            $table->string('ruta')->nullable();
            $table->string('telefono')->nullable();
            $table->string('modalidad')->nullable();

            // INFORMACIÓN DISCIPLINARIA
            $table->date('fecha_falta')->nullable();
            $table->string('tipo_falta')->nullable();
            $table->text('descripcion_falta')->nullable();

            // DOCUMENTO DE LA FALTA
            $table->string('documento_falta')->nullable();

            // OBSERVACIONES Y PROCESO
            $table->text('observacion')->nullable();
            $table->text('descargos')->nullable();
            $table->text('decision_final')->nullable();

            // ESTADO DEL PROCESO
            $table->string('estado')->default('Pendiente');

            // USUARIO QUE REGISTRÓ EL PROCESO
            $table->unsignedBigInteger('user_id')->nullable();

            // RELACIÓN CON USERS
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disciplinario');
    }
}