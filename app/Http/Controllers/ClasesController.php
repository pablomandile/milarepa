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
use App\Models\Modalidad;
use App\Models\Stream;

class ClasesController extends Controller
{
    public function index()
    {
        $clases = Clase::with(['ciclo', 'entidad', 'maestros', 'coordinador', 'esquemaPrecio', 'modalidad', 'stream', 'imagen'])
            ->orderByDesc('created_at')
            ->get();

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
            'modalidades' => Modalidad::orderBy('nombre')->get(),
            'streams' => Stream::orderBy('nombre')->get(),
        ]);
    }

    public function store(ClaseRequest $request)
    {
        $validated = $request->validated();
        $maestrosIds = $validated['maestro_ids'] ?? [];
        unset($validated['maestro_ids']);

        $clase = Clase::create($validated);
        $clase->maestros()->sync($maestrosIds);

        return redirect()->route('clases.index');
    }

    public function show(string $id)
    {
        //
    }

    public function showPublic(Request $request, Clase $clase)
    {
        $clase->load(['imagen', 'entidad', 'maestros.imagen', 'coordinador', 'ciclo']);
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
        $clase = Clase::with(['imagen', 'maestros'])->findOrFail($id);

        return inertia('Clases/Edit', [
            'clase' => $clase,
            'ciclos' => Ciclo::orderBy('nombre')->get(),
            'entidades' => Entidad::orderBy('nombre')->get(),
            'maestros' => Maestro::orderBy('nombre')->get(),
            'coordinadores' => Coordinador::orderBy('nombre')->get(),
            'esquemaPrecios' => EsquemaPrecio::orderBy('nombre')->get(),
            'modalidades' => Modalidad::orderBy('nombre')->get(),
            'streams' => Stream::orderBy('nombre')->get(),
        ]);
    }

    public function update(ClaseRequest $request, $id)
    {
        $validated = $request->validated();
        $maestrosIds = $validated['maestro_ids'] ?? [];
        unset($validated['maestro_ids']);

        $clase = Clase::findOrFail($id);
        $clase->update($validated);
        $clase->maestros()->sync($maestrosIds);

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

    public function updateEstado(Request $request, Clase $clase)
    {
        $validated = $request->validate([
            'activa' => ['required', 'boolean'],
        ]);

        $clase->activa = (bool) $validated['activa'];
        $clase->save();

        return back()->with('success', 'Estado de la clase actualizado.');
    }
}

