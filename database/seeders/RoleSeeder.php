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

        $permission_create_lugares = Permission::create(['name' => 'create lugares']);
        $permission_create_lugares = Permission::create(['name' => 'update lugares']);
        $permission_create_lugares = Permission::create(['name' => 'read lugares']);
        $permission_create_lugares = Permission::create(['name' => 'delete lugares']);

        $permission_create_disponibilidades = Permission::create(['name' => 'create disponibilidades']);
        $permission_create_disponibilidades = Permission::create(['name' => 'update disponibilidades']);
        $permission_create_disponibilidades = Permission::create(['name' => 'read disponibilidades']);
        $permission_create_disponibilidades = Permission::create(['name' => 'delete disponibilidades']);

        $permission_create_maestros = Permission::create(['name' => 'create maestros']);
        $permission_create_maestros = Permission::create(['name' => 'update maestros']);
        $permission_create_maestros = Permission::create(['name' => 'read maestros']);
        $permission_create_maestros = Permission::create(['name' => 'delete maestros']);

        $permission_create_coordinadores = Permission::create(['name' => 'create coordinadores']);
        $permission_create_coordinadores = Permission::create(['name' => 'update coordinadores']);
        $permission_create_coordinadores = Permission::create(['name' => 'read coordinadores']);
        $permission_create_coordinadores = Permission::create(['name' => 'delete coordinadores']);

        $permission_create_monedas = Permission::create(['name' => 'create monedas']);
        $permission_create_monedas = Permission::create(['name' => 'update monedas']);
        $permission_create_monedas = Permission::create(['name' => 'read monedas']);
        $permission_create_monedas = Permission::create(['name' => 'delete monedas']);

        $permission_create_esquema_precios = Permission::create(['name' => 'create esquema_precios']);
        $permission_create_esquema_precios = Permission::create(['name' => 'update esquema_precios']);
        $permission_create_esquema_precios = Permission::create(['name' => 'read esquema_precios']);
        $permission_create_esquema_precios = Permission::create(['name' => 'delete esquema_precios']);

        $permission_create_aplica_descuento = Permission::create(['name' => 'create aplica_descuento']);
        $permission_create_aplica_descuento = Permission::create(['name' => 'update aplica_descuento']);
        $permission_create_aplica_descuento = Permission::create(['name' => 'read aplica_descuento']);
        $permission_create_aplica_descuento = Permission::create(['name' => 'delete aplica_descuento']);

        $permission_create_membresias = Permission::create(['name' => 'create membresias']);
        $permission_create_membresias = Permission::create(['name' => 'update membresias']);
        $permission_create_membresias = Permission::create(['name' => 'read membresias']);
        $permission_create_membresias = Permission::create(['name' => 'delete membresias']);

        $permission_create_tipo_actividades = Permission::create(['name' => 'create tipo_actividades']);
        $permission_create_tipo_actividades = Permission::create(['name' => 'update tipo_actividades']);
        $permission_create_tipo_actividades = Permission::create(['name' => 'read tipo_actividades']);
        $permission_create_tipo_actividades = Permission::create(['name' => 'delete tipo_actividades']);

        $permission_create_esquema_descuentos = Permission::create(['name' => 'create esquema_descuentos']);
        $permission_create_esquema_descuentos = Permission::create(['name' => 'update esquema_descuentos']);
        $permission_create_esquema_descuentos = Permission::create(['name' => 'read esquema_descuentos']);
        $permission_create_esquema_descuentos = Permission::create(['name' => 'delete esquema_descuentos']);

        $permission_create_metodos_pago = Permission::create(['name' => 'create metodos_pago']);
        $permission_create_metodos_pago = Permission::create(['name' => 'update metodos_pago']);
        $permission_create_metodos_pago = Permission::create(['name' => 'read metodos_pago']);
        $permission_create_metodos_pago = Permission::create(['name' => 'delete metodos_pago']);

        $permission_create_actividades = Permission::create(['name' => 'create actividades']);
        $permission_create_actividades = Permission::create(['name' => 'update actividades']);
        $permission_create_actividades = Permission::create(['name' => 'read actividades']);
        $permission_create_actividades = Permission::create(['name' => 'delete actividades']);

        $permissions_admin = [
            'create roles',
            'read roles',
            'update roles',
            'delete roles',
            'create lugares',
            'update lugares',
            'read lugares',
            'delete lugares',
            'create disponibilidades',
            'update disponibilidades',
            'read disponibilidades',
            'delete disponibilidades',
            'create maestros',
            'update maestros',
            'read maestros',
            'delete maestros',
            'create coordinadores',
            'update coordinadores',
            'read coordinadores',
            'delete coordinadores',
            'create monedas',
            'update monedas',
            'read monedas',
            'delete monedas',
            'create esquema_precios',
            'update esquema_precios',
            'read esquema_precios',
            'delete esquema_precios',
            'create aplica_descuento',
            'update aplica_descuento',
            'read aplica_descuento',
            'delete aplica_descuento',
            'create membresias',
            'update membresias',
            'read membresias',
            'delete membresias',
            'create tipo_actividades',
            'update tipo_actividades',
            'read tipo_actividades',
            'delete tipo_actividades',
            'create esquema_descuentos',
            'update esquema_descuentos',
            'read esquema_descuentos',
            'delete esquema_descuentos',
            'create metodos_pago',
            'update metodos_pago',
            'read metodos_pago',
            'delete metodos_pago',
            'create actividades',
            'update actividades',
            'read actividades',
            'delete actividades',
        ];
        
        $permissions_editor = [
            'create lugares',
            'update lugares',
            'read lugares',
            'delete lugares',
            'create disponibilidades',
            'update disponibilidades',
            'read disponibilidades',
            'delete disponibilidades',
            'create maestros',
            'update maestros',
            'read maestros',
            'delete maestros',
            'create coordinadores',
            'update coordinadores',
            'read coordinadores',
            'delete coordinadores',
            'create monedas',
            'update monedas',
            'read monedas',
            'delete monedas',
            'create esquema_precios',
            'update esquema_precios',
            'read esquema_precios',
            'delete esquema_precios',
            'create aplica_descuento',
            'update aplica_descuento',
            'read aplica_descuento',
            'delete aplica_descuento',
            'create membresias',
            'update membresias',
            'read membresias',
            'delete membresias',
            'create tipo_actividades',
            'update tipo_actividades',
            'read tipo_actividades',
            'delete tipo_actividades',
            'create esquema_descuentos',
            'update esquema_descuentos',
            'read esquema_descuentos',
            'delete esquema_descuentos',
            'create metodos_pago',
            'update metodos_pago',
            'read metodos_pago',
            'delete metodos_pago',
            'create actividades',
            'update actividades',
            'read actividades',
            'delete actividades',
        ];

        $permissions_data_entry = [
            'create lugares',
            'update lugares',
            'read lugares',
            'delete lugares',
            'create coordinadores',
            'update coordinadores',
            'read coordinadores',
            'delete coordinadores',
            'create actividades',
            'update actividades',
            'read actividades',
            'delete actividades',
        ];

        $role_admin->syncPermissions($permissions_admin);
        $role_editor->syncPermissions($permissions_editor);
        
    }
}
