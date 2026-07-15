<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\EstadoCuentaMembresia;
use App\Models\Inscripcion;
use App\Models\InscripcionClase;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Inertia\Inertia;

/**
 * Vista unificada del ledger de cobros: lista los cobros de todos los dominios
 * (actividades, clases, membresías, ventas) normalizados para un DataTable.
 */
class CobrosController extends Controller
{
    public function index()
    {
        $cobros = Cobro::query()
            ->with([
                'metodoPago:id,nombre',
                'comprobantes.imagen:id,ruta',
                'cobrable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        Inscripcion::class => ['actividad:id,nombre', 'user:id,name', 'guestUser:id,name'],
                        InscripcionClase::class => ['clase:id,nombre'],
                        EstadoCuentaMembresia::class => ['user:id,name', 'membresia:id,nombre'],
                        Venta::class => ['libro:id,titulo', 'entidad:id,nombre'],
                    ]);
                },
            ])
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->get();

        $rows = $cobros->map(function (Cobro $cobro) {
            [$dominio, $detalle] = $this->describir($cobro);

            return [
                'id' => $cobro->id,
                'fecha' => optional($cobro->fecha_pago)->toDateString() ?? optional($cobro->created_at)->toDateString(),
                'dominio' => $dominio,
                'detalle' => $detalle,
                'monto' => (float) $cobro->monto,
                'medio' => $cobro->metodoPago?->nombre,
                'referencia' => $cobro->referencia,
                'observaciones' => $cobro->observaciones,
                'origen' => $cobro->origen,
                'comprobante' => $cobro->comprobantes->first()?->ruta,
            ];
        });

        return Inertia::render('Cobros/Index', [
            'cobros' => $rows,
        ]);
    }

    /**
     * Devuelve [dominio legible, detalle] para un cobro según su cobrable polimórfico.
     */
    private function describir(Cobro $cobro): array
    {
        $c = $cobro->cobrable;

        return match ($cobro->cobrable_type) {
            'inscripcion' => [
                'Actividad',
                trim(($c?->user?->name ?? $c?->guestUser?->name ?? 'Sin persona') . ' — ' . ($c?->actividad?->nombre ?? 'Actividad #' . $cobro->cobrable_id)),
            ],
            'inscripcion_clase' => [
                'Clase',
                trim(($c?->nombre_snapshot ?? 'Sin persona') . ' — ' . ($c?->clase?->nombre ?? 'Clase #' . $cobro->cobrable_id)),
            ],
            'membresia_cuota' => [
                'Membresía',
                trim(($c?->user?->name ?? 'Sin socio') . ' — ' . ($c?->membresia?->nombre ?? 'Membresía') . ' (' . ($c?->mes_pagado ?? '') . ')'),
            ],
            'venta' => [
                'Venta',
                trim(($c?->libro?->titulo ?? 'Libro') . ($c?->entidad?->nombre ? ' — ' . $c->entidad->nombre : '')),
            ],
            default => [$cobro->cobrable_type ?? '—', ''],
        };
    }
}
