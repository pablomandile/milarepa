<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermisoRequest;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermisosController extends Controller
{
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Permisos/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function store(PermisoRequest $request)
    {
        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        return redirect()->route('permisos.create')->with('success', 'Permiso creado correctamente.');
    }

    public function edit(Permission $permiso)
    {
        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Permisos/Edit', [
            'permiso' => $permiso,
            'permissions' => $permissions,
        ]);
    }

    public function update(PermisoRequest $request, Permission $permiso)
    {
        $permiso->update([
            'name' => $request->name,
        ]);

        return redirect()->route('permisos.create')->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permiso)
    {
        try {
            $permiso->delete();
            return redirect()->route('permisos.create')->with('success', 'Permiso eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('permisos.create')->with('error', 'Error al eliminar el permiso: ' . $e->getMessage());
        }
    }
}
