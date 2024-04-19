<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role_admin = Role::create (['name' => 'admin']);
        $role_editor = Role::create ([ 'name' => 'editor']);

        $permission_create_role = Permission::create(['name' => 'create roles']);
        $permission_create_role = Permission::create(['name' => 'read roles']);
        $permission_create_role = Permission::create(['name' => 'update roles']);
        $permission_create_role = Permission::create(['name' => 'delete roles']);

        // $permission_create_role = Permission::create(['name' => 'create actividades']);
        // $permission_create_role = Permission::create(['name' => 'read actividades']);
        // $permission_create_role = Permission::create(['name' => 'update actividades']);
        // $permission_create_role = Permission::create(['name' => 'delete actividades']);

    }
}
