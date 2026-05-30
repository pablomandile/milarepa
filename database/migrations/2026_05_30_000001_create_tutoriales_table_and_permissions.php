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
        if (!Schema::hasTable('tutoriales')) {
            Schema::create('tutoriales', function (Blueprint $table) {
                $table->id();
                $table->string('url', 500);
                $table->string('descripcion', 255);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $permisos = [
            'create tutoriales',
            'read tutoriales',
            'update tutoriales',
            'delete tutoriales',
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
        Schema::dropIfExists('tutoriales');

        Permission::whereIn('name', [
            'create tutoriales',
            'read tutoriales',
            'update tutoriales',
            'delete tutoriales',
        ])->delete();
    }
};
