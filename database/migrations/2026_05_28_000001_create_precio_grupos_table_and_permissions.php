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
        if (!Schema::hasTable('precio_grupos')) {
            Schema::create('precio_grupos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->date('fecha_desde')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('precio_grupo_membresias')) {
            Schema::create('precio_grupo_membresias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('precio_grupo_id')->constrained('precio_grupos')->cascadeOnDelete();
                $table->foreignId('membresia_id')->constrained('membresias');
                $table->decimal('valor', 10, 2)->default(0);
                $table->timestamps();
                $table->unique(['precio_grupo_id', 'membresia_id'], 'precio_grupo_membresia_unique');
            });
        }

        $permisos = [
            'create precio-grupos',
            'read precio-grupos',
            'update precio-grupos',
            'delete precio-grupos',
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
        Schema::dropIfExists('precio_grupo_membresias');
        Schema::dropIfExists('precio_grupos');

        Permission::whereIn('name', [
            'create precio-grupos',
            'read precio-grupos',
            'update precio-grupos',
            'delete precio-grupos',
        ])->delete();
    }
};
