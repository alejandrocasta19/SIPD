<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UnifyUserRolesToAdminAndEquipo extends Migration
{
    public function up()
    {
        DB::table('users')->where('role', 'coordinadora')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'abogado')->update(['role' => 'equipo']);

        DB::table('users')->where('email', 'coordinadora@sipd.co')->update([
            'role' => 'admin',
            'cargo' => 'Coordinadora de RH',
        ]);

        DB::table('users')->where('email', 'kelly.johanna.rodriguez@pendiente.local')->update([
            'role' => 'equipo',
            'cargo' => 'Asesora jurídica',
        ]);
    }

    public function down()
    {
        DB::table('users')->where('email', 'coordinadora@sipd.co')->update([
            'role' => 'coordinadora',
            'cargo' => 'Coordinadora de RH',
        ]);
        DB::table('users')->where('email', 'kelly.johanna.rodriguez@pendiente.local')->update([
            'role' => 'abogado',
            'cargo' => 'Equipo de RH',
        ]);
        DB::table('users')->where('role', 'admin')->where('email', '!=', 'coordinadora@sipd.co')
            ->update(['role' => 'coordinadora']);
        DB::table('users')->where('role', 'equipo')->update(['role' => 'abogado']);
    }
}
