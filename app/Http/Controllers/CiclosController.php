<?php

namespace App\Http\Controllers;

use App\Http\Requests\CicloRequest;
use App\Models\Ciclo;
use Inertia\Inertia;

class CiclosController extends Controller
{
    public function index()
    {
        $ciclos = Ciclo::orderBy('nombre')->paginate(15);

        return inertia('Ciclos/Index', ['ciclos' => $ciclos]);
    }

    public function create()
    {
        return inertia('Ciclos/Create');
    }

    public function store(CicloRequest $request)
    {
        Ciclo::create($request->validated());

        return redirect()->route('ciclos.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit($id)
    {
        $ciclo = Ciclo::findOrFail($id);

        return Inertia::render('Ciclos/Edit', [
            'ciclo' => $ciclo,
        ]);
    }

    public function update(CicloRequest $request, $id)
    {
        $ciclo = Ciclo::findOrFail($id);
        $ciclo->update($request->validated());

        return redirect()->route('ciclos.index');
    }

    public function destroy($id)
    {
        try {
            $ciclo = Ciclo::findOrFail($id);
            $ciclo->delete();

            return redirect()->route('ciclos.index')->with('success', 'Ciclo eliminado con exito.');
        } catch (\Throwable $e) {
            return redirect()->route('ciclos.index')->with('error', 'Error al eliminar el ciclo: ' . $e->getMessage());
        }
    }
}

