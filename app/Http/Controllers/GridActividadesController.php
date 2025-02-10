<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Actividad;
use Carbon\Carbon;


class GridActividadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $actividades = Actividad::with([
            'tipoActividad',
            'descripcion',
            'imagen',
            'entidad',
            'disponibilidad',
            'modalidad',
            'esquemaPrecio.membresias.membresia',
            'esquemaDescuento',
            'stream',
            'grabacion',
            'programa',
            'metodosPago', 
            'hospedajes', 
            'comidas', 
            'transportes',
            'maestros',
            'coordinadores'
        ])->get();

        // Convertir cada fecha con Carbon a un string amigable:
        $actividades->transform(function ($actividad) {
        // Si la columna es “fecha_inicio”, haz:
        $date = Carbon::parse($actividad->fecha_inicio);

        // Formato “30 de Enero 00:00 hs.”
        // “j” = día sin cero, “F” = Mes completo, “H:i” = 24h:mins
        $actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';

        return $actividad;
    });

        // dd($actividades->toArray());
        return inertia('GridActividades/Index', ['actividades' => $actividades->toArray()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
