<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSipdAlertaSilenciosTable extends Migration
{
    public function up()
    {
        Schema::create('sipd_alerta_silencios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 32);
            $table->unsignedBigInteger('proceso_id');
            $table->timestamps();
            $table->unique(['user_id', 'tipo', 'proceso_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sipd_alerta_silencios');
    }
}
