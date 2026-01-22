<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VersionesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para versiones
        Permission::firstOrCreate(['name' => 'create versiones']);
        Permission::firstOrCreate(['name' => 'update versiones']);
        Permission::firstOrCreate(['name' => 'read versiones']);
        Permission::firstOrCreate(['name' => 'delete versiones']);

        // Asignar permisos al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'create versiones',
                'update versiones',
                'read versiones',
                'delete versiones'
            ]);
        }

        // Asignar permisos al rol editor
        $editorRole = Role::where('name', 'editor')->first();
        if ($editorRole) {
            $editorRole->givePermissionTo([
                'create versiones',
                'update versiones',
                'read versiones',
                'delete versiones'
            ]);
        }
    }
}
