<?php

namespace App\Http\Controllers;

use App\Http\Requests\LugarRequest;
use App\Models\Lugar;
use Inertia\Inertia;

class LugaresController extends Controller
{
    public function index()
    {
        $lugares = Lugar::orderBy('nombre')->get();

        return Inertia::render('Lugares/Index', [
            'lugares' => $lugares,
        ]);
    }

    public function create()
    {
        return Inertia::render('Lugares/Create');
    }

    public function store(LugarRequest $request)
    {
        Lugar::create($request->validated());

        return redirect()->route('lugares.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $lugar = Lugar::findOrFail($id);

        return Inertia::render('Lugares/Edit', [
            'lugar' => $lugar,
        ]);
    }

    public function update(LugarRequest $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $lugar->update($request->validated());

        return redirect()->route('lugares.index');
    }

    public function destroy(string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $lugar->delete();

        return redirect()->route('lugares.index');
    }
}

