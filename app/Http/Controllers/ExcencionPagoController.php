<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExcencionPagoRequest;
use App\Models\Actividad;
use App\Models\ExcencionPago;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExcencionPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $excenciones = ExcencionPago::with(['user', 'actividad'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return inertia('ExcencionPago/Index', [
            'excenciones' => $excenciones,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $actividades = Actividad::select('id', 'nombre', 'fecha_inicio')
            ->whereDate('fecha_inicio', '>=', Carbon::now()->startOfMonth())
            ->orderBy('fecha_inicio')
            ->get();

        return inertia('ExcencionPago/Create', [
            'usuarios' => $usuarios,
            'actividades' => $actividades,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExcencionPagoRequest $request)
    {
        ExcencionPago::create($request->validated());

        return redirect()->route('excencionpago.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExcencionPago $excencionpago)
    {
        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $actividades = Actividad::select('id', 'nombre', 'fecha_inicio')
            ->whereDate('fecha_inicio', '>=', Carbon::now()->startOfMonth())
            ->orWhere('id', $excencionpago->actividad_id)
            ->orderBy('fecha_inicio')
            ->get();

        return Inertia::render('ExcencionPago/Edit', [
            'excencion' => $excencionpago->load(['user', 'actividad']),
            'usuarios' => $usuarios,
            'actividades' => $actividades,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExcencionPagoRequest $request, ExcencionPago $excencionpago)
    {
        $excencionpago->update($request->validated());

        return redirect()->route('excencionpago.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExcencionPago $excencionpago)
    {
        $excencionpago->delete();

        return redirect()->route('excencionpago.index');
    }
}
