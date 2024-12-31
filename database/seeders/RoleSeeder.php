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
        $role_admin = Role::create ([ 'name' => 'admin' ]);
        $role_editor = Role::create ([ 'name' => 'editor' ]);
        $role_asistant = Role::create ([ 'name' => 'asistant' ]);


        $permission_create_role = Permission::create(['name' => 'create roles']);
        $permission_create_role = Permission::create(['name' => 'read roles']);
        $permission_create_role = Permission::create(['name' => 'update roles']);
        $permission_create_role = Permission::create(['name' => 'delete roles']);

        $permission_create_entidades = Permission::create(['name' => 'create entidades']);
        $permission_create_entidades = Permission::create(['name' => 'update entidades']);
        $permission_create_entidades = Permission::create(['name' => 'read entidades']);
        $permission_create_entidades = Permission::create(['name' => 'delete entidades']);

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

        $permission_create_tipos_actividad = Permission::create(['name' => 'create tipos_actividad']);
        $permission_create_tipos_actividad = Permission::create(['name' => 'update tipos_actividad']);
        $permission_create_tipos_actividad = Permission::create(['name' => 'read tipos_actividad']);
        $permission_create_tipos_actividad = Permission::create(['name' => 'delete tipos_actividad']);

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

        $permission_create_comidas = Permission::create(['name' => 'create comidas']);
        $permission_create_comidas = Permission::create(['name' => 'update comidas']);
        $permission_create_comidas = Permission::create(['name' => 'read comidas']);
        $permission_create_comidas = Permission::create(['name' => 'delete comidas']);
        
        $permission_create_hospedajes = Permission::create(['name' => 'create hospedajes']);
        $permission_create_hospedajes = Permission::create(['name' => 'update hospedajes']);
        $permission_create_hospedajes = Permission::create(['name' => 'read hospedajes']);
        $permission_create_hospedajes = Permission::create(['name' => 'delete hospedajes']);

        $permission_create_lugares_hospedaje = Permission::create(['name' => 'create lugares_hospedaje']);
        $permission_create_lugares_hospedaje = Permission::create(['name' => 'update lugares_hospedaje']);
        $permission_create_lugares_hospedaje = Permission::create(['name' => 'read lugares_hospedaje']);
        $permission_create_lugares_hospedaje = Permission::create(['name' => 'delete lugares_hospedaje']);

        $permission_create_modalidades = Permission::create(['name' => 'create modalidades']);
        $permission_create_modalidades = Permission::create(['name' => 'update modalidades']);
        $permission_create_modalidades = Permission::create(['name' => 'read modalidades']);
        $permission_create_modalidades = Permission::create(['name' => 'delete modalidades']);

        $permission_create_tickets = Permission::create(['name' => 'create tickets']);
        $permission_create_tickets = Permission::create(['name' => 'update tickets']);
        $permission_create_tickets = Permission::create(['name' => 'read tickets']);
        $permission_create_tickets = Permission::create(['name' => 'delete tickets']);
        $permission_create_tickets = Permission::create(['name' => 'asign tickets']);


        $permissions_admin = [
            'create roles',
            'read roles',
            'update roles',
            'delete roles',
            'create entidades',
            'update entidades',
            'read entidades',
            'delete entidades',
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
            'create comidas',
            'update comidas',
            'read comidas',
            'delete comidas',
            'create hospedajes',
            'update hospedajes',
            'read hospedajes',
            'delete hospedajes',
            'create lugares_hospedaje',
            'update lugares_hospedaje',
            'read lugares_hospedaje',
            'delete lugares_hospedaje',
            'create modalidades',
            'update modalidades',
            'read modalidades',
            'delete modalidades',
            'create tickets',
            'update tickets',
            'read tickets',
            'delete tickets',
            'asign tickets',
        ];
        
        $permissions_editor = [
            'create entidades',
            'update entidades',
            'read entidades',
            'delete entidades',
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
            'create comidas',
            'update comidas',
            'read comidas',
            'delete comidas',
            'create hospedajes',
            'update hospedajes',
            'read hospedajes',
            'delete hospedajes',
            'create lugares_hospedaje',
            'update lugares_hospedaje',
            'read lugares_hospedaje',
            'delete lugares_hospedaje',
            'create modalidades',
            'update modalidades',
            'read modalidades',
            'delete modalidades',
            'create tickets',
            'update tickets',
            'read tickets',
            'delete tickets',
            'asign tickets',
        ];

        $permissions_asistant = [
            'read actividades',
            'create tickets',
            'update tickets',
            'read tickets',
            'delete tickets',
        ];

        $role_admin->syncPermissions($permissions_admin);
        $role_editor->syncPermissions($permissions_editor);
        $role_asistant->syncPermissions($permissions_asistant);

    }
}
