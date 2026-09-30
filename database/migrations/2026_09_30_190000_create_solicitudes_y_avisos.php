<?php

use App\Support\RhPermisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesYAvisos extends Migration
{
    public function up()
    {
        Schema::create('permiso_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permiso', 64);
            $table->text('que_hara');
            $table->text('motivo');
            $table->unsignedSmallInteger('horas');
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedBigInteger('responded_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('respuesta')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });

        Schema::create('sipd_avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('remitente_id')->nullable();
            $table->string('tipo', 32);
            $table->string('titulo');
            $table->text('cuerpo')->nullable();
            $table->text('motivo')->nullable();
            $table->unsignedBigInteger('proceso_id')->nullable();
            $table->unsignedBigInteger('solicitud_id')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'leida_at']);
        });

        $restringidos = [
            RhPermisos::EDITAR_CASOS,
            RhPermisos::ELIMINAR_CASOS,
            RhPermisos::ELIMINAR_ANEXOS,
            RhPermisos::EDITAR_PERFIL,
        ];

        DB::table('user_permisos')
            ->whereIn('permiso', $restringidos)
            ->whereIn('user_id', function ($query) {
                $query->select('id')->from('users')->where('role', 'abogado');
            })
            ->delete();
    }

    public function down()
    {
        Schema::dropIfExists('sipd_avisos');
        Schema::dropIfExists('permiso_solicitudes');
    }
}
