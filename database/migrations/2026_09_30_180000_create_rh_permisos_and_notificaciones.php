<?php

use App\Support\RhPermisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRhPermisosAndNotificaciones extends Migration
{
    public function up()
    {
        Schema::create('user_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permiso', 64);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'permiso']);
        });

        Schema::create('coordinadora_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 32);
            $table->unsignedBigInteger('proceso_id')->nullable();
            $table->string('titulo');
            $table->text('cuerpo')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();
            $table->index(['tipo', 'leida_at']);
        });

        $now = now();
        $abogados = DB::table('users')->where('role', 'abogado')->pluck('id');
        foreach ($abogados as $userId) {
            foreach (RhPermisos::porDefecto() as $permiso) {
                DB::table('user_permisos')->insert([
                    'user_id' => $userId,
                    'permiso' => $permiso,
                    'expires_at' => null,
                    'granted_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('coordinadora_notificaciones');
        Schema::dropIfExists('user_permisos');
    }
}
