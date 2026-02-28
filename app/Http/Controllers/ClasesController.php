<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaseRequest;
use Illuminate\Http\Request;
use App\Models\Ciclo;
use App\Models\Clase;
use App\Models\Coordinador;
use App\Models\Descripcion;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Maestro;

class ClasesController extends Controller
{
    public function index()
    {
        $clases = Clase::with(['ciclo', 'entidad', 'maestro', 'coordinador', 'esquemaPrecio', 'imagen'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return inertia('Clases/Index', ['clases' => $clases]);
    }

    public function create()
    {
        return inertia('Clases/Create', [
            'ciclos' => Ciclo::orderBy('nombre')->get(),
            'entidades' => Entidad::orderBy('nombre')->get(),
            'maestros' => Maestro::orderBy('nombre')->get(),
            'coordinadores' => Coordinador::orderBy('nombre')->get(),
            'esquemaPrecios' => EsquemaPrecio::orderBy('nombre')->get(),
        ]);
    }

    public function store(ClaseRequest $request)
    {
        Clase::create($request->validated());

        return redirect()->route('clases.index');
    }

    public function show(string $id)
    {
        //
    }

    public function showPublic(Request $request, Clase $clase)
    {
        $clase->load(['imagen', 'entidad', 'maestro.imagen', 'coordinador', 'ciclo']);
        $descripciones = Descripcion::query()
            ->where('nombre', 'Clase PG')
            ->orWhere('nombre', 'like', 'Estructura de una sesi%')
            ->get();

        $descripcionClasePg = $descripciones->firstWhere('nombre', 'Clase PG');
        $descripcionEstructuraSesion = $descripciones->first(function ($item) {
            return str_starts_with((string) $item->nombre, 'Estructura de una sesi');
        });

        return inertia('Clases/ShowPublic', [
            'clase' => $clase,
            'returnUrl' => $request->query('return_url'),
            'descripcionesCards' => [
                'clasePg' => $descripcionClasePg,
                'estructuraSesion' => $descripcionEstructuraSesion,
            ],
        ]);
    }

    public function edit($id)
    {
        $clase = Clase::with('imagen')->findOrFail($id);

        return inertia('Clases/Edit', [
            'clase' => $clase,
            'ciclos' => Ciclo::orderBy('nombre')->get(),
            'entidades' => Entidad::orderBy('nombre')->get(),
            'maestros' => Maestro::orderBy('nombre')->get(),
            'coordinadores' => Coordinador::orderBy('nombre')->get(),
            'esquemaPrecios' => EsquemaPrecio::orderBy('nombre')->get(),
        ]);
    }

    public function update(ClaseRequest $request, $id)
    {
        $clase = Clase::findOrFail($id);
        $clase->update($request->validated());

        return redirect()->route('clases.index');
    }

    public function destroy($id)
    {
        try {
            $clase = Clase::findOrFail($id);
            $clase->delete();

            return redirect()->route('clases.index')->with('success', 'Clase eliminada con exito.');
        } catch (\Throwable $e) {
            return redirect()->route('clases.index')->with('error', 'Error al eliminar la clase: ' . $e->getMessage());
        }
    }
}

