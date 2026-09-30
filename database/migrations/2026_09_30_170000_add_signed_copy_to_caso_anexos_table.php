<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignedCopyToCasoAnexosTable extends Migration
{
    public function up()
    {
        Schema::table('caso_anexos', function (Blueprint $table) {
            $table->string('ruta_firmada')->nullable()->after('ruta_segura');
            $table->string('nombre_firmado')->nullable()->after('ruta_firmada');
            $table->string('extension_firmada', 10)->nullable()->after('nombre_firmado');
            $table->string('mime_firmado', 100)->nullable()->after('extension_firmada');
            $table->unsignedBigInteger('tamano_firmado')->nullable()->after('mime_firmado');
        });
    }

    public function down()
    {
        Schema::table('caso_anexos', function (Blueprint $table) {
            $table->dropColumn([
                'ruta_firmada',
                'nombre_firmado',
                'extension_firmada',
                'mime_firmado',
                'tamano_firmado',
            ]);
        });
    }
}
