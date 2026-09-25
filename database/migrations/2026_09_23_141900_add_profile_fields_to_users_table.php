<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono')->nullable()->after('cargo');
            $table->string('cedula')->nullable()->after('telefono');
            $table->date('fecha_ingreso')->nullable()->after('cedula');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'cedula', 'fecha_ingreso']);
        });
    }
}
