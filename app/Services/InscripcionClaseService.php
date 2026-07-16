<?php

namespace App\Services;

use App\Models\Clase;
use App\Models\GuestUser;
use App\Models\InscripcionClase;
use App\Models\Libro;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Persistencia de una inscripción a clase con líneas de producto de las 4 categorías
 * Tharpa (libro/oracion/arte/otro) e inventario por categoría. NO registra el cobro
 * (lo hace el llamador: el controller para el checkout de clases, el POS para su línea).
 * Debe invocarse dentro de una transacción (usa lockForUpdate vía ProductoTharpaService).
 */
class InscripcionClaseService
{
    /** payload key con los IDs seleccionados, por categoría. */
    private const CATEGORIA_IDS = [
        'libro' => 'libro_ids',
        'oracion' => 'oracion_ids',
        'arte' => 'arte_ids',
        'otro' => 'otro_ids',
    ];

    public function __construct(private ProductoTharpaService $productos)
    {
    }

    public function persistir(array $validated, ?InscripcionClase $existente = null, ?int $vendedorId = null): InscripcionClase
    {
        $email = Str::lower(trim((string) ($validated['email'] ?? '')));
        $registrarDatos = (bool) ($validated['registrar_datos'] ?? false);

        $clase = Clase::with(['esquemaPrecio.membresias.membresia:id,nombre'])->findOrFail($validated['clase_id']);
        $datosPrecio = $this->resolverDatosPreciosClase($clase);

        $membresiaSeleccionada = trim((string) $validated['membresia']);
        $montoActividad = $this->resolverMontoActividadPorMembresia($datosPrecio['membresias'], $membresiaSeleccionada);

        // Líneas de producto deseadas (4 categorías), con precio server-authoritative.
        $deseadas = $this->lineasDeseadas($validated);

        $montoTharpa = round(array_sum(array_map(fn ($l) => $l['subtotal'], $deseadas)), 2);
        $montoTienda = (float) ($validated['montoTienda'] ?? 0);
        $montoApagar = round($montoActividad + $montoTharpa + $montoTienda, 2);
        $articulosTharpa = implode(', ', array_filter(array_map(fn ($l) => $l['titulo'], $deseadas)));

        // Resolver usuario / invitado.
        $user = User::where('email', $email)->first();
        $guestUser = null;
        if (!$user) {
            $datos = [
                'name' => $validated['nombre'] ?? 'Sin nombre',
                'email' => $email,
                'provincia_id' => $validated['provincia_id'] ?? null,
                'municipio_id' => $validated['municipio_id'] ?? null,
                'barrio_id' => $validated['barrio_id'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
            ];
            if ($registrarDatos) {
                $user = User::create($datos + ['password' => Hash::make(Str::random(24))]);
            } else {
                $guestUser = GuestUser::create($datos);
            }
        }
        $userId = $user?->id;

        if ($userId !== null) {
            $duplicada = InscripcionClase::query()
                ->where('clase_id', $validated['clase_id'])
                ->where('user_id', $userId)
                ->when($existente, fn ($q) => $q->where('id', '!=', $existente->id))
                ->exists();
            if ($duplicada) {
                throw ValidationException::withMessages(['email' => 'El usuario ya está inscripto en esta clase.']);
            }
        }

        $payload = [
            'clase_id' => $validated['clase_id'],
            'user_id' => $userId,
            'guest_user_id' => $userId ? null : $guestUser?->id,
            'nombre_snapshot' => $user?->name ?? $guestUser?->name ?? ($validated['nombre'] ?? null),
            'email_snapshot' => $user?->email ?? $guestUser?->email ?? $email,
            'membresia' => $membresiaSeleccionada,
            'precioGeneral' => $datosPrecio['precio_general'],
            'montoActividad' => $montoActividad,
            'montoTharpa' => $montoTharpa,
            'montoTienda' => $montoTienda,
            'articulos_tienda' => $validated['articulos_tienda'] ?? null,
            'articulos_tharpa' => $articulosTharpa,
            'montoApagar' => $montoApagar,
            'pago' => $validated['pago'],
            'online' => (bool) $validated['online'],
        ];

        // Inscripción destino: edición / restaurar soft-deleted / nueva.
        $inscripcion = $existente;
        if (!$inscripcion && $userId !== null) {
            $inscripcion = InscripcionClase::withTrashed()
                ->where('clase_id', $validated['clase_id'])
                ->where('user_id', $userId)
                ->whereNotNull('deleted_at')
                ->latest('id')
                ->first();
            if ($inscripcion) {
                $inscripcion->restore();
            }
        }

        // Selección previa (para el diff de stock) ANTES de tocar los items.
        $previas = $this->lineasPrevias($inscripcion);

        if ($inscripcion) {
            $inscripcion->update($payload);
        } else {
            $inscripcion = InscripcionClase::create($payload);
        }

        $this->sincronizarProductos($inscripcion, (int) ($clase->entidad_id ?? 0), $deseadas, $previas, [
            'fecha' => now(),
            'vendedor_id' => $vendedorId,
            'email_comprador' => $email,
        ]);

        return $inscripcion;
    }

    /** @return array<int, array{categoria:string,producto_id:int,producto_type:string,cantidad:int,precio_unitario:float,subtotal:float,titulo:string}> */
    private function lineasDeseadas(array $validated): array
    {
        $lineas = [];

        foreach (self::CATEGORIA_IDS as $categoria => $key) {
            $ids = collect($validated[$key] ?? []);
            if ($categoria === 'libro') {
                // Compat con el payload viejo del checkout de clases.
                $ids = $ids->merge($validated['libros_tharpa_ids'] ?? []);
            }
            $ids = $ids->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();

            foreach ($ids as $id) {
                $producto = $this->productos->buscarProducto($categoria, $id);
                if (!$producto) {
                    continue;
                }
                $precio = (float) $producto->precio;
                $lineas[] = [
                    'categoria' => $categoria,
                    'producto_id' => $id,
                    'producto_type' => $categoria, // alias morphMap
                    'cantidad' => 1,
                    'precio_unitario' => $precio,
                    'subtotal' => round($precio, 2),
                    'titulo' => (string) $producto->titulo,
                ];
            }
        }

        return $lineas;
    }

    /** Cantidades previas por "categoria:producto_id" (desde items; o desde articulos_tharpa por título si es fila vieja). */
    private function lineasPrevias(?InscripcionClase $inscripcion): array
    {
        if (!$inscripcion) {
            return [];
        }

        $items = $inscripcion->items()->get();
        if ($items->isNotEmpty()) {
            $prev = [];
            foreach ($items as $item) {
                $prev[$item->categoria . ':' . (int) $item->producto_id] = (int) $item->cantidad;
            }

            return $prev;
        }

        // Fila vieja (sin items): sembrar por título, solo libros, para conciliar stock.
        $titulos = collect(preg_split('/\s*,\s*/', (string) ($inscripcion->articulos_tharpa ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->values();
        if ($titulos->isEmpty()) {
            return [];
        }

        $prev = [];
        foreach (Libro::whereIn('titulo', $titulos->all())->pluck('id') as $id) {
            $prev['libro:' . (int) $id] = 1;
        }

        return $prev;
    }

    private function sincronizarProductos(InscripcionClase $inscripcion, int $entidadId, array $deseadas, array $previas, array $ctx): void
    {
        $deseadasMap = [];
        foreach ($deseadas as $l) {
            $deseadasMap[$l['categoria'] . ':' . $l['producto_id']] = $l;
        }

        $keys = array_unique(array_merge(array_keys($deseadasMap), array_keys($previas)));
        foreach ($keys as $key) {
            [$categoria, $productoId] = explode(':', $key);
            $productoId = (int) $productoId;
            $qDeseada = isset($deseadasMap[$key]) ? (int) $deseadasMap[$key]['cantidad'] : 0;
            $qPrevia = (int) ($previas[$key] ?? 0);
            $delta = $qDeseada - $qPrevia;

            if ($delta > 0) {
                $this->productos->descontarStock($categoria, $productoId, $entidadId, $delta, $ctx);
            } elseif ($delta < 0) {
                $this->productos->devolverStock($categoria, $productoId, $entidadId, -$delta, $ctx);
            }
        }

        // Reemplazar los items por la selección deseada (snapshot de precio).
        $inscripcion->items()->delete();
        foreach ($deseadas as $l) {
            $inscripcion->items()->create([
                'categoria' => $l['categoria'],
                'producto_type' => $l['producto_type'],
                'producto_id' => $l['producto_id'],
                'cantidad' => $l['cantidad'],
                'precio_unitario' => $l['precio_unitario'],
                'subtotal' => $l['subtotal'],
            ]);
        }
    }

    public function resolverDatosPreciosClase(Clase $clase): array
    {
        $lineas = collect($clase->esquemaPrecio?->membresias ?? []);

        $membresias = $lineas
            ->map(fn ($linea) => ['nombre' => $linea->membresia?->nombre, 'precio' => (float) $linea->precio])
            ->filter(fn ($linea) => !empty($linea['nombre']))
            ->values();

        $lineaSinMembresia = $membresias->first(function ($linea) {
            return $this->normalizarTexto((string) $linea['nombre']) === 'sin membresia'
                || str_contains($this->normalizarTexto((string) $linea['nombre']), 'sin membresia');
        });

        if (!$lineaSinMembresia) {
            throw ValidationException::withMessages(['clase_id' => 'falta precio general para la clase']);
        }

        return [
            'precio_general' => (float) $lineaSinMembresia['precio'],
            'membresias' => $membresias->all(),
        ];
    }

    public function resolverMontoActividadPorMembresia(array $membresias, string $membresiaSeleccionada): float
    {
        $membresia = collect($membresias)->first(function ($item) use ($membresiaSeleccionada) {
            return $this->normalizarTexto((string) ($item['nombre'] ?? '')) === $this->normalizarTexto($membresiaSeleccionada);
        });

        if (!$membresia) {
            throw ValidationException::withMessages([
                'membresia' => 'La membresía seleccionada no existe en el esquema de precios de la clase.',
            ]);
        }

        return (float) ($membresia['precio'] ?? 0);
    }

    private function normalizarTexto(string $texto): string
    {
        return Str::of($texto)->ascii()->lower()->trim()->value();
    }
}
