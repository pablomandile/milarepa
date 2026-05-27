<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_estudios', function (Blueprint $table) {
            if (!Schema::hasColumn('programa_estudios', 'abreviacion')) {
                $table->string('abreviacion', 10)->nullable()->after('nombre');
            }
        });

        $permisos = [
            'create programa-estudios',
            'read programa-estudios',
            'update programa-estudios',
            'delete programa-estudios',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permisos);
        }
    }

    public function down(): void
    {
        Schema::table('programa_estudios', function (Blueprint $table) {
            if (Schema::hasColumn('programa_estudios', 'abreviacion')) {
                $table->dropColumn('abreviacion');
            }
        });

        Permission::whereIn('name', [
            'create programa-estudios',
            'read programa-estudios',
            'update programa-estudios',
            'delete programa-estudios',
        ])->delete();
    }
};
