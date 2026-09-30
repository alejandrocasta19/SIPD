<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirmaPathToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'firma_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('firma_path')->nullable()->after('cedula');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'firma_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('firma_path');
            });
        }
    }
}
