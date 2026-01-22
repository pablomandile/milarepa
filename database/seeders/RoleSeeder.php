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
        $role_admin = Role::firstOrCreate([ 'name' => 'admin' ]);
        $role_editor = Role::firstOrCreate([ 'name' => 'editor' ]);
        $role_asistant = Role::firstOrCreate([ 'name' => 'asistant' ]);


        $permission_create_role = Permission::firstOrCreate(['name' => 'create roles']);
        $permission_create_role = Permission::firstOrCreate(['name' => 'read roles']);
        $permission_create_role = Permission::firstOrCreate(['name' => 'update roles']);
        $permission_create_role = Permission::firstOrCreate(['name' => 'delete roles']);

        $permission_create_entidades = Permission::firstOrCreate(['name' => 'create entidades']);
        $permission_create_entidades = Permission::firstOrCreate(['name' => 'update entidades']);
        $permission_create_entidades = Permission::firstOrCreate(['name' => 'read entidades']);
        $permission_create_entidades = Permission::firstOrCreate(['name' => 'delete entidades']);

        $permission_create_disponibilidades = Permission::firstOrCreate(['name' => 'create disponibilidades']);
        $permission_create_disponibilidades = Permission::firstOrCreate(['name' => 'update disponibilidades']);
        $permission_create_disponibilidades = Permission::firstOrCreate(['name' => 'read disponibilidades']);
        $permission_create_disponibilidades = Permission::firstOrCreate(['name' => 'delete disponibilidades']);

        $permission_create_maestros = Permission::firstOrCreate(['name' => 'create maestros']);
        $permission_create_maestros = Permission::firstOrCreate(['name' => 'update maestros']);
        $permission_create_maestros = Permission::firstOrCreate(['name' => 'read maestros']);
        $permission_create_maestros = Permission::firstOrCreate(['name' => 'delete maestros']);

        $permission_create_coordinadores = Permission::firstOrCreate(['name' => 'create coordinadores']);
        $permission_create_coordinadores = Permission::firstOrCreate(['name' => 'update coordinadores']);
        $permission_create_coordinadores = Permission::firstOrCreate(['name' => 'read coordinadores']);
        $permission_create_coordinadores = Permission::firstOrCreate(['name' => 'delete coordinadores']);

        $permission_create_monedas = Permission::firstOrCreate(['name' => 'create monedas']);
        $permission_create_monedas = Permission::firstOrCreate(['name' => 'update monedas']);
        $permission_create_monedas = Permission::firstOrCreate(['name' => 'read monedas']);
        $permission_create_monedas = Permission::firstOrCreate(['name' => 'delete monedas']);

        $permission_create_esquema_precios = Permission::firstOrCreate(['name' => 'create esquema_precios']);
        $permission_create_esquema_precios = Permission::firstOrCreate(['name' => 'update esquema_precios']);
        $permission_create_esquema_precios = Permission::firstOrCreate(['name' => 'read esquema_precios']);
        $permission_create_esquema_precios = Permission::firstOrCreate(['name' => 'delete esquema_precios']);

        $permission_create_aplica_descuento = Permission::firstOrCreate(['name' => 'create aplica_descuento']);
        $permission_create_aplica_descuento = Permission::firstOrCreate(['name' => 'update aplica_descuento']);
        $permission_create_aplica_descuento = Permission::firstOrCreate(['name' => 'read aplica_descuento']);
        $permission_create_aplica_descuento = Permission::firstOrCreate(['name' => 'delete aplica_descuento']);

        $permission_create_membresias = Permission::firstOrCreate(['name' => 'create membresias']);
        $permission_create_membresias = Permission::firstOrCreate(['name' => 'update membresias']);
        $permission_create_membresias = Permission::firstOrCreate(['name' => 'read membresias']);
        $permission_create_membresias = Permission::firstOrCreate(['name' => 'delete membresias']);

        $permission_create_tipos_actividad = Permission::firstOrCreate(['name' => 'create tipos_actividad']);
        $permission_create_tipos_actividad = Permission::firstOrCreate(['name' => 'update tipos_actividad']);
        $permission_create_tipos_actividad = Permission::firstOrCreate(['name' => 'read tipos_actividad']);
        $permission_create_tipos_actividad = Permission::firstOrCreate(['name' => 'delete tipos_actividad']);

        $permission_create_esquema_descuentos = Permission::firstOrCreate(['name' => 'create esquema_descuentos']);
        $permission_create_esquema_descuentos = Permission::firstOrCreate(['name' => 'update esquema_descuentos']);
        $permission_create_esquema_descuentos = Permission::firstOrCreate(['name' => 'read esquema_descuentos']);
        $permission_create_esquema_descuentos = Permission::firstOrCreate(['name' => 'delete esquema_descuentos']);

        $permission_create_metodos_pago = Permission::firstOrCreate(['name' => 'create metodos_pago']);
        $permission_create_metodos_pago = Permission::firstOrCreate(['name' => 'update metodos_pago']);
        $permission_create_metodos_pago = Permission::firstOrCreate(['name' => 'read metodos_pago']);
        $permission_create_metodos_pago = Permission::firstOrCreate(['name' => 'delete metodos_pago']);

        $permission_create_actividades = Permission::firstOrCreate(['name' => 'create actividades']);
        $permission_create_actividades = Permission::firstOrCreate(['name' => 'update actividades']);
        $permission_create_actividades = Permission::firstOrCreate(['name' => 'read actividades']);
        $permission_create_actividades = Permission::firstOrCreate(['name' => 'delete actividades']);

        $permission_create_comidas = Permission::firstOrCreate(['name' => 'create comidas']);
        $permission_create_comidas = Permission::firstOrCreate(['name' => 'update comidas']);
        $permission_create_comidas = Permission::firstOrCreate(['name' => 'read comidas']);
        $permission_create_comidas = Permission::firstOrCreate(['name' => 'delete comidas']);
        
        $permission_create_hospedajes = Permission::firstOrCreate(['name' => 'create hospedajes']);
        $permission_create_hospedajes = Permission::firstOrCreate(['name' => 'update hospedajes']);
        $permission_create_hospedajes = Permission::firstOrCreate(['name' => 'read hospedajes']);
        $permission_create_hospedajes = Permission::firstOrCreate(['name' => 'delete hospedajes']);

        $permission_create_lugares_hospedaje = Permission::firstOrCreate(['name' => 'create lugares_hospedaje']);
        $permission_create_lugares_hospedaje = Permission::firstOrCreate(['name' => 'update lugares_hospedaje']);
        $permission_create_lugares_hospedaje = Permission::firstOrCreate(['name' => 'read lugares_hospedaje']);
        $permission_create_lugares_hospedaje = Permission::firstOrCreate(['name' => 'delete lugares_hospedaje']);

        $permission_create_modalidades = Permission::firstOrCreate(['name' => 'create modalidades']);
        $permission_create_modalidades = Permission::firstOrCreate(['name' => 'update modalidades']);
        $permission_create_modalidades = Permission::firstOrCreate(['name' => 'read modalidades']);
        $permission_create_modalidades = Permission::firstOrCreate(['name' => 'delete modalidades']);

        $permission_create_tickets = Permission::firstOrCreate(['name' => 'create tickets']);
        $permission_create_tickets = Permission::firstOrCreate(['name' => 'update tickets']);
        $permission_create_tickets = Permission::firstOrCreate(['name' => 'read tickets']);
        $permission_create_tickets = Permission::firstOrCreate(['name' => 'delete tickets']);
        $permission_create_tickets = Permission::firstOrCreate(['name' => 'asign tickets']);

        $permission_create_transportes = Permission::firstOrCreate(['name' => 'create transportes']);
        $permission_create_transportes = Permission::firstOrCreate(['name' => 'update transportes']);
        $permission_create_transportes = Permission::firstOrCreate(['name' => 'read transportes']);
        $permission_create_transportes = Permission::firstOrCreate(['name' => 'delete transportes']);

        $permission_create_streams = Permission::firstOrCreate(['name' => 'create streams']);
        $permission_create_streams = Permission::firstOrCreate(['name' => 'update streams']);
        $permission_create_streams = Permission::firstOrCreate(['name' => 'read streams']);
        $permission_create_streams = Permission::firstOrCreate(['name' => 'delete streams']);

        $permission_create_novedades = Permission::firstOrCreate(['name' => 'create novedades']);
        $permission_create_novedades = Permission::firstOrCreate(['name' => 'update novedades']);
        $permission_create_novedades = Permission::firstOrCreate(['name' => 'read novedades']);
        $permission_create_novedades = Permission::firstOrCreate(['name' => 'delete novedades']);

        $permission_create_versiones = Permission::firstOrCreate(['name' => 'create versiones']);
        $permission_create_versiones = Permission::firstOrCreate(['name' => 'update versiones']);
        $permission_create_versiones = Permission::firstOrCreate(['name' => 'read versiones']);
        $permission_create_versiones = Permission::firstOrCreate(['name' => 'delete versiones']);

        $permission_create_usuarios = Permission::firstOrCreate(['name' => 'create usuarios']);
        $permission_create_usuarios = Permission::firstOrCreate(['name' => 'update usuarios']);
        $permission_create_usuarios = Permission::firstOrCreate(['name' => 'read usuarios']);
        $permission_create_usuarios = Permission::firstOrCreate(['name' => 'delete usuarios']);

        $permission_create_grabaciones = Permission::firstOrCreate(['name' => 'create grabaciones']);
        $permission_create_grabaciones = Permission::firstOrCreate(['name' => 'update grabaciones']);
        $permission_create_grabaciones = Permission::firstOrCreate(['name' => 'read grabaciones']);
        $permission_create_grabaciones = Permission::firstOrCreate(['name' => 'delete grabaciones']);


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
            'create tipos_actividad',
            'update tipos_actividad',
            'read tipos_actividad',
            'delete tipos_actividad',
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
            'create transportes',
            'update transportes',
            'read transportes',
            'delete transportes',
            'create streams',
            'update streams',
            'read streams',
            'delete streams',
            'create novedades',
            'update novedades',
            'read novedades',
            'delete novedades',
            'create versiones',
            'update versiones',
            'read versiones',
            'delete versiones',
            'create usuarios',
            'update usuarios',
            'read usuarios',
            'delete usuarios',
            'create grabaciones',
            'update grabaciones',
            'read grabaciones',
            'delete grabaciones',
                                    
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
            'create tipos_actividad',
            'update tipos_actividad',
            'read tipos_actividad',
            'delete tipos_actividad',
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
            'create transportes',
            'update transportes',
            'read transportes',
            'delete transportes',
            'create streams',
            'update streams',
            'read streams',
            'delete streams',
            'create novedades',
            'update novedades',
            'read novedades',
            'delete novedades',
            'create versiones',
            'update versiones',
            'read versiones',
            'delete versiones',
            'create usuarios',
            'update usuarios',
            'read usuarios',
            'delete usuarios',
            'create grabaciones',
            'update grabaciones',
            'read grabaciones',
            'delete grabaciones',
        ];

        $permissions_asistant = [
            'read actividades',
            'create tickets',
            'update tickets',
            'read tickets',
            'delete tickets',
            'read novedades',
            
        ];

        $role_admin->syncPermissions($permissions_admin);
        $role_editor->syncPermissions($permissions_editor);
        $role_asistant->syncPermissions($permissions_asistant);

    }
}


