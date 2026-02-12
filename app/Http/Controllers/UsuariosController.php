<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;


class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuarios = User::with(['roles', 'membresia'])->paginate(15);
        return inertia('Users/Index', ['usuarios' => $usuarios]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return inertia('Users/Create', ['roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'telefono' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'es_maestro' => 'nullable|boolean',
            'es_coordinador' => 'nullable|boolean',
            'roles' => 'required|string|exists:roles,name'
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['es_maestro'] = $validated['es_maestro'] ? 1 : 0;
        $validated['es_coordinador'] = $validated['es_coordinador'] ? 1 : 0;

        $user = User::create($validated);
        $user->syncRoles([$validated['roles']]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $usuario = User::with(['roles', 'membresia'])->findOrFail($id);
        $roles = Role::all();
        return inertia('Users/Edit', [
            'usuario' => $usuario,
            'roles' => $roles
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telefono' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'es_maestro' => 'nullable|boolean',
            'es_coordinador' => 'nullable|boolean',
            'roles' => 'required|string|exists:roles,name'
        ]);

        if ($validated['password']) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['es_maestro'] = $validated['es_maestro'] ? 1 : 0;
        $validated['es_coordinador'] = $validated['es_coordinador'] ? 1 : 0;

        $usuario->update($validated);
        $usuario->syncRoles([$validated['roles']]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado exitosamente');
    }
}
