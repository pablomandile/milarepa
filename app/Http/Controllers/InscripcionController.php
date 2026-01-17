<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\EstadoTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InscripcionController extends Controller
{
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
}
