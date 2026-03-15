<?php

namespace App\Http\Controllers;

use App\Models\EnvioMail;
use Illuminate\Http\Request;

class EnvioCorreosController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'destinatario' => (string) $request->string('destinatario')->trim(),
            'motivo' => (string) $request->string('motivo')->trim(),
            'fecha' => (string) $request->string('fecha')->trim(),
            'tipo' => (string) $request->string('tipo')->trim(),
        ];

        $envios = EnvioMail::query()
            ->with('user:id,name')
            ->when($filters['destinatario'] !== '', function ($query) use ($filters) {
                $query->where('destinatario', 'like', '%' . $filters['destinatario'] . '%');
            })
            ->when($filters['motivo'] !== '', function ($query) use ($filters) {
                $query->where('motivo', 'like', '%' . $filters['motivo'] . '%');
            })
            ->when($filters['fecha'] !== '', function ($query) use ($filters) {
                $query->whereDate('fecha', $filters['fecha']);
            })
            ->when($filters['tipo'] !== '', function ($query) use ($filters) {
                $query->where('tipo', $filters['tipo']);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $fechasDisponibles = EnvioMail::query()
            ->selectRaw('DATE(fecha) as fecha')
            ->distinct()
            ->orderByDesc('fecha')
            ->pluck('fecha')
            ->filter()
            ->values();

        return inertia('EnvioCorreos/Index', [
            'envios' => $envios,
            'filters' => $filters,
            'fechasDisponibles' => $fechasDisponibles,
            'tipos' => ['Automático', 'Manual'],
        ]);
    }
}
