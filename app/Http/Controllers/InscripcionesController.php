<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\EstadoTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class InscripcionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inscripciones = Inscripcion::with(['actividad', 'estado', 'hospedaje', 'comida', 'transporte'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Agregar fecha formateada a las actividades
        $inscripciones->transform(function ($inscripcion) {
            $date = Carbon::parse($inscripcion->actividad->fecha_inicio);
            $inscripcion->actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';
            return $inscripcion;
        });

        return Inertia::render('Inscripciones/Index', [
            'inscripciones' => $inscripciones,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'actividad_id' => 'required|exists:actividades,id',
            'user_id' => 'required|exists:users,id',
            'membresia' => 'required|string',
            'precioGeneral' => 'required|numeric',
            'montoapagar' => 'required|numeric',
            'pago' => 'required|in:total,parcial,impago',
            'estado_id' => 'required|exists:estados_ticket,id',
            'envioLinkStream' => 'required|in:enviado,pendiente',
            'envioGrabación' => 'required|in:enviada,pendiente',
            'comprobante' => 'nullable|string',
            'asistencia' => 'required|in:presente,ausente',
            'online' => 'required|boolean',
            'hospedaje_id' => 'nullable|exists:hospedajes,id',
            'comida_id' => 'nullable|exists:comidas,id',
            'transporte_id' => 'nullable|exists:transportes,id',
        ]);

        $inscripcion = Inscripcion::create($request->all());

        return Inertia::render('Inscripcion/Show', [
            'inscripcion' => $inscripcion->load(['actividad', 'user', 'estado', 'hospedaje', 'comida', 'transporte']),
        ]);
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
