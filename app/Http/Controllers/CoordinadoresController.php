<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coordinador;
use App\Models\User;
use App\Http\Requests\CoordinadorRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;


class CoordinadoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coordinadores = Coordinador::get();
        return inertia('Coordinadores/Index', ['coordinadores' => $coordinadores]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Coordinadores/Create');
    }

    /**
     * Importa como coordinadores a los usuarios marcados con "Es coordinador".
     * Evita duplicados por email (no actualiza los que ya existen).
     */
    public function importarDesdeUsuarios()
    {
        $usuarios = User::where('es_coordinador', true)->get(['name', 'email', 'telefono']);

        // Emails ya presentes en coordinadores (normalizados) para evitar duplicados.
        $emailsExistentes = Coordinador::whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => Str::lower(trim((string) $email)))
            ->filter()
            ->flip();

        $creados = 0;
        $existentes = 0;
        $sinEmail = 0;

        DB::transaction(function () use ($usuarios, &$emailsExistentes, &$creados, &$existentes, &$sinEmail) {
            foreach ($usuarios as $usuario) {
                $email = trim((string) $usuario->email);

                if ($email === '') {
                    $sinEmail++;
                    continue;
                }

                $clave = Str::lower($email);
                if ($emailsExistentes->has($clave)) {
                    $existentes++;
                    continue;
                }

                Coordinador::create([
                    'nombre' => Str::limit((string) $usuario->name, 50, ''),
                    'email' => $email,
                    'telefono' => $usuario->telefono ? Str::limit((string) $usuario->telefono, 50, '') : null,
                ]);

                // Registra el email para no duplicar si dos usuarios comparten el mismo.
                $emailsExistentes->put($clave, true);
                $creados++;
            }
        });

        $partes = ["{$creados} coordinador(es) agregado(s)"];
        if ($existentes > 0) {
            $partes[] = "{$existentes} ya existían";
        }
        if ($sinEmail > 0) {
            $partes[] = "{$sinEmail} sin email (omitidos)";
        }

        return redirect()->route('coordinadores.index')
            ->with('success', 'Importación desde usuarios: ' . implode(', ', $partes) . '.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CoordinadorRequest $request)
    {
        Coordinador::create($request->validated());
        return redirect()->route('coordinadores.index');
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
    public function edit($id)
    {
        $coordinador = Coordinador::findOrFail($id);

        // Devolver la vista de edición
        return Inertia::render('Coordinadores/Edit', [
            'coordinador' => $coordinador,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CoordinadorRequest $request, $id)
    {
        $coordinador = Coordinador::findOrFail($id);

        $coordinador->update($request->validated());

        return redirect()->route('coordinadores.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $coordinador = Coordinador::findorfail($id);
            $coordinador->delete();
            return redirect()->route('coordinadores.index')->with('success', 'Coordinador eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('coordinadores.index')->with('error', 'Error al eliminar el Coordinador: ' . $e->getMessage());
        }
    }
}
