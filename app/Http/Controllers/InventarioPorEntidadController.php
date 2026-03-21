<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use App\Models\InventarioEntidadLibro;
use Illuminate\Support\Collection;
use Inertia\Response;

class InventarioPorEntidadController extends Controller
{
    public function index(): Response
    {
        $entidadPrincipal = Entidad::query()
            ->where('entidad_principal', true)
            ->first(['id', 'nombre']);

        $inventarioEntidad = InventarioEntidadLibro::query()
            ->with(['entidad:id,nombre,entidad_principal', 'libro:id,titulo'])
            ->get();

        $rows = $inventarioEntidad
            ->filter(fn ($item) => (int) $item->cantidad !== 0)
            ->map(function ($item) use ($entidadPrincipal) {
                $esPrincipal = $entidadPrincipal && (int) $item->entidad_id === (int) $entidadPrincipal->id;

                return [
                    'entidad_id' => (int) $item->entidad_id,
                    'entidad_nombre' => $item->entidad?->nombre ?? 'Entidad sin nombre',
                    'libro_id' => (int) $item->libro_id,
                    'libro_titulo' => $item->libro?->titulo ?? 'Sin libro',
                    'cantidad' => (int) $item->cantidad,
                    'orden' => $esPrincipal ? 0 : 1,
                ];
            });

        $inventarioPorEntidad = $this->ordenarRows($rows);

        return inertia('InventarioPorEntidad/Index', [
            'inventarioPorEntidad' => $inventarioPorEntidad,
        ]);
    }

    private function ordenarRows(Collection $rows): array
    {
        return $rows
            ->sort(function (array $a, array $b) {
                if ($a['orden'] !== $b['orden']) {
                    return $a['orden'] <=> $b['orden'];
                }

                if ($a['entidad_nombre'] !== $b['entidad_nombre']) {
                    return strnatcasecmp($a['entidad_nombre'], $b['entidad_nombre']);
                }

                return strnatcasecmp($a['libro_titulo'], $b['libro_titulo']);
            })
            ->values()
            ->all();
    }
}
