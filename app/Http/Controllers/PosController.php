<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\PosRequest;
use App\Models\Actividad;
use App\Models\Arte;
use App\Models\Barrio;
use App\Models\CategoriaTienda;
use App\Models\Clase;
use App\Models\Entidad;
use App\Models\Libro;
use App\Models\MetodoPago;
use App\Models\Municipio;
use App\Models\Oracion;
use App\Models\Otro;
use App\Models\Provincia;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPos;
use App\Models\VentaPosItem;
use App\Services\CobroService;
use App\Services\InscripcionActividadService;
use App\Services\InscripcionClaseService;
use App\Services\OptimizadorImagenService;
use App\Services\ProductoTharpaService;
use App\Services\ProductoTiendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class PosController extends Controller
{
    use ProcesaImagenAlGuardar;

    /** Categorías de producto que se venden por línea de ticket (no inscripciones). */
    private const CATEGORIAS_PRODUCTO = ['libro', 'oracion', 'arte', 'otro'];

    public function __construct(
        private ProductoTharpaService $productos,
        private ProductoTiendaService $tienda,
    ) {
    }

    public function index(): Response
    {
        $mesActual = now()->format('Y-m');

        return inertia('Pos/Index', [
            'entidades' => Entidad::query()->orderBy('nombre')->get(['id', 'nombre']),
            'entidadPrincipalId' => (int) Entidad::query()->where('entidad_principal', true)->value('id'),
            'metodosPago' => MetodoPago::query()->orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => $this->productos->categorias(),
            'categoriasTienda' => CategoriaTienda::query()->orderBy('orden')->orderBy('nombre')->get(['id', 'nombre']),
            // Datos para la línea "inscripción a clase" (reusa InscripcionClaseForm).
            'clases' => Clase::query()
                ->where('activa', true)
                ->where('mes_referencia', $mesActual)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'esquema_precio_id', 'entidad_id']),
            'librosTharpa' => Libro::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'oracionesTharpa' => Oracion::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'arteTharpa' => Arte::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'otrosTharpa' => Otro::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
            'barrios' => Barrio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
            // Datos para la línea "inscripción a actividad".
            'actividades' => Actividad::query()
                ->where('estado', true)
                ->orderByDesc('fecha_inicio')
                ->get(['id', 'nombre']),
        ]);
    }

    /** Servicios (grabación/comidas/transportes/hospedajes) de una actividad para el POS. */
    public function datosActividad(Actividad $actividad)
    {
        $actividad->load(['grabacion:id,nombre,valor', 'comidas:id,nombre,precio', 'transportes:id,nombre,precio', 'hospedajes:id,nombre,precio']);

        return response()->json([
            'actividad' => [
                'id' => $actividad->id,
                'nombre' => $actividad->nombre,
                'grabacion' => $actividad->grabacion ? ['id' => $actividad->grabacion->id, 'nombre' => $actividad->grabacion->nombre, 'valor' => (float) $actividad->grabacion->valor] : null,
                'comidas' => $actividad->comidas->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'precio' => (float) $c->precio])->values(),
                'transportes' => $actividad->transportes->map(fn ($t) => ['id' => $t->id, 'nombre' => $t->nombre, 'precio' => (float) $t->precio])->values(),
                'hospedajes' => $actividad->hospedajes->map(fn ($h) => ['id' => $h->id, 'nombre' => $h->nombre, 'precio' => (float) $h->precio])->values(),
            ],
        ]);
    }

    /** Cotiza (sin persistir) el monto de una inscripción a actividad para el POS. */
    public function cotizarActividad(Request $request, InscripcionActividadService $service)
    {
        $request->validate([
            'actividad_id' => ['required', 'integer', 'exists:actividades,id'],
            'email' => ['nullable', 'email'],
        ]);

        return response()->json($service->cotizar($request->all()));
    }

    /**
     * Catálogo de una categoría con stock disponible en la entidad (para el carrito).
     * `ambito` (default 'tharpa') decide de dónde sale el producto:
     *   - tharpa → categoria ∈ {libro,oracion,arte,otro} (ProductoTharpaService).
     *   - tienda → categoria = id de categorias_tienda (ProductoTiendaService).
     * El default 'tharpa' mantiene compatibilidad con InscripcionClaseForm (no manda ambito).
     */
    public function productos(Request $request)
    {
        $ambito = (string) $request->query('ambito', 'tharpa');

        if ($ambito === 'tienda') {
            $data = $request->validate([
                'categoria' => ['required', 'integer', 'exists:categorias_tienda,id'],
                'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
            ]);

            return response()->json([
                'productos' => $this->tienda->listarPorEntidad((int) $data['categoria'], (int) $data['entidad_id']),
            ]);
        }

        $data = $request->validate([
            'categoria' => ['required', Rule::in($this->productos->categorias())],
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        return response()->json([
            'productos' => $this->productos->listarPorEntidad($data['categoria'], (int) $data['entidad_id']),
        ]);
    }

    /** Autocomplete de alumno/cliente (mismo patrón que EstadoInscripciones). */
    public function buscarUsuarios(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['usuarios' => []]);
        }

        $usuarios = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json(['usuarios' => $usuarios]);
    }

    /**
     * Finaliza la venta POS: crea el header, por cada línea descuenta stock (por categoría),
     * registra un cobro por ítem con referencia "Por POS #id", y setea el total = suma.
     * Todo transaccional (guardarConImagen abre la transacción y limpia la imagen si falla).
     */
    public function store(PosRequest $request, OptimizadorImagenService $optimizador, CobroService $cobroService, InscripcionClaseService $inscripcionClaseService, InscripcionActividadService $inscripcionActividadService): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($data['idempotency_key']) && VentaPos::query()->where('idempotency_key', $data['idempotency_key'])->exists()) {
            return redirect()->route('pos.index')->with('success', 'La venta ya estaba registrada.');
        }

        $this->guardarConImagen($request->file('comprobante'), 'img/mpago', $optimizador, function ($imagenId) use ($data, $cobroService, $inscripcionClaseService, $inscripcionActividadService) {
            $vendedorId = auth()->id();
            $fecha = $data['fecha'] ?? now()->toDateString();
            $metodoId = (int) $data['metodo_pago_id'];
            $entidadId = (int) $data['entidad_id'];
            $comprobanteId = $imagenId ?? ($data['comprobante_id'] ?? null);
            $comprobantes = array_filter([$comprobanteId]);
            $metodoNombre = MetodoPago::query()->whereKey($metodoId)->value('nombre') ?: 'POS';

            $venta = VentaPos::create([
                'fecha' => $fecha,
                'vendedor_id' => $vendedorId,
                'cliente_user_id' => $data['cliente_user_id'] ?? null,
                'entidad_id' => $entidadId,
                'metodo_pago_id' => $metodoId,
                'comprobante_id' => $comprobanteId,
                'total' => 0,
                'observaciones' => $data['observaciones'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            $ref = "Por POS #{$venta->id}";
            $ctx = ['fecha' => $fecha, 'vendedor_id' => $vendedorId];
            $total = 0.0;

            foreach ($data['items'] as $linea) {
                $tipo = $linea['tipo'];
                $cantidad = max(1, (int) ($linea['cantidad'] ?? 1));

                if ($tipo === 'inscripcion_clase') {
                    // Crea la InscripcionClase (con sus productos y descuento de stock por categoría)
                    // y la cobra por el ticket → queda Saldada (recalcular: true).
                    $ins = $inscripcionClaseService->persistir($linea['inscripcion'] ?? [], null, $vendedorId);
                    $subtotal = round((float) $ins->totalAdeudado(), 2);

                    $item = VentaPosItem::create([
                        'venta_pos_id' => $venta->id,
                        'tipo' => $tipo,
                        'vendible_type' => $ins->getMorphClass(),
                        'vendible_id' => $ins->getKey(),
                        'cantidad' => 1,
                        'precio_unitario' => $subtotal,
                        'subtotal' => $subtotal,
                        'descripcion' => 'Clase: ' . ($ins->clase?->nombre ?? '') . ' — ' . ($ins->nombre_snapshot ?? ''),
                    ]);

                    $cobro = $cobroService->registrar($ins, [
                        'monto' => $subtotal,
                        'fecha_pago' => $fecha,
                        'metodo_pago_id' => $metodoId,
                        'referencia' => $ref,
                        'comprobante_ids' => $comprobantes,
                        'registrado_por' => $vendedorId,
                        'origen' => 'pos',
                    ], recalcular: true);

                    $item->update(['cobro_id' => $cobro->id]);
                    $total += $subtotal;
                    continue;
                }

                if ($tipo === 'inscripcion_actividad') {
                    $ins = $inscripcionActividadService->crearDesdePayload($linea['inscripcion'] ?? [], $vendedorId);
                    $subtotal = round((float) $ins->totalAdeudado(), 2);

                    $item = VentaPosItem::create([
                        'venta_pos_id' => $venta->id,
                        'tipo' => $tipo,
                        'vendible_type' => $ins->getMorphClass(),
                        'vendible_id' => $ins->getKey(),
                        'cantidad' => 1,
                        'precio_unitario' => $subtotal,
                        'subtotal' => $subtotal,
                        'descripcion' => 'Actividad: ' . ($ins->actividad?->nombre ?? '') . ' — ' . ($ins->user?->name ?? $ins->guestUser?->name ?? ''),
                    ]);

                    $cobro = $cobroService->registrar($ins, [
                        'monto' => $subtotal,
                        'fecha_pago' => $fecha,
                        'metodo_pago_id' => $metodoId,
                        'referencia' => $ref,
                        'comprobante_ids' => $comprobantes,
                        'registrado_por' => $vendedorId,
                        'origen' => 'pos',
                    ], recalcular: true);

                    $item->update(['cobro_id' => $cobro->id]);
                    $total += $subtotal;
                    continue;
                }

                if ($tipo === 'articulo_tienda') {
                    // Artículo de la Tienda general: descuenta su propio inventario y
                    // se cobra sobre la línea del ticket (igual que oracion/arte/otro).
                    $productoId = (int) ($linea['producto_id'] ?? 0);
                    $producto = $this->tienda->buscarProducto($productoId);
                    if (!$producto) {
                        throw ValidationException::withMessages(['items' => 'Uno de los artículos de tienda del carrito no existe.']);
                    }

                    $precioU = (float) $producto->precio;
                    $subtotal = round($precioU * $cantidad, 2);

                    $this->tienda->descontarStock($productoId, $entidadId, $cantidad, $ctx);

                    $item = VentaPosItem::create([
                        'venta_pos_id' => $venta->id,
                        'tipo' => $tipo,
                        'vendible_type' => $producto->getMorphClass(),
                        'vendible_id' => $producto->getKey(),
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioU,
                        'subtotal' => $subtotal,
                        'descripcion' => trim(($producto->titulo ?? '') . ' — ' . ($producto->categoria?->nombre ?? 'Tienda')),
                    ]);

                    $cobro = $cobroService->registrar($item, [
                        'monto' => $subtotal,
                        'fecha_pago' => $fecha,
                        'metodo_pago_id' => $metodoId,
                        'referencia' => $ref,
                        'comprobante_ids' => $comprobantes,
                        'registrado_por' => $vendedorId,
                        'origen' => 'pos',
                    ], recalcular: false);

                    $item->update(['cobro_id' => $cobro->id]);
                    $total += $subtotal;
                    continue;
                }

                if (!in_array($tipo, self::CATEGORIAS_PRODUCTO, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Tipo de ítem no válido.',
                    ]);
                }

                $productoId = (int) ($linea['producto_id'] ?? 0);
                $producto = $this->productos->buscarProducto($tipo, $productoId);
                if (!$producto) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos del carrito no existe.']);
                }

                $precioU = (float) $producto->precio;
                $subtotal = round($precioU * $cantidad, 2);
                $descripcion = trim(($producto->titulo ?? '') . ($producto->tipo ? " ({$producto->tipo})" : ''));

                // Descuento de stock (para libro además registra HistoricoPedidoLibro).
                $this->productos->descontarStock($tipo, $productoId, $entidadId, $cantidad, $ctx);

                if ($tipo === 'libro') {
                    // Cobrable = Venta: la venta de libro aparece en el reporte de Ventas de libros.
                    $venta_libro = Venta::create([
                        'fecha' => $fecha,
                        'entidad_id' => $entidadId,
                        'libro_id' => $productoId,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioU,
                        'montoTotal' => $subtotal,
                        'modo' => $metodoNombre,
                        'comprobante_id' => $comprobanteId,
                        'vendedor_id' => $vendedorId,
                    ]);

                    $item = VentaPosItem::create([
                        'venta_pos_id' => $venta->id,
                        'tipo' => $tipo,
                        'vendible_type' => $venta_libro->getMorphClass(),
                        'vendible_id' => $venta_libro->getKey(),
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioU,
                        'subtotal' => $subtotal,
                        'descripcion' => $descripcion,
                    ]);

                    $cobrable = $venta_libro;
                } else {
                    // Cobrable = la línea del ticket; vendible = el registro de catálogo (referencia).
                    $item = VentaPosItem::create([
                        'venta_pos_id' => $venta->id,
                        'tipo' => $tipo,
                        'vendible_type' => $producto->getMorphClass(),
                        'vendible_id' => $producto->getKey(),
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioU,
                        'subtotal' => $subtotal,
                        'descripcion' => $descripcion,
                    ]);

                    $cobrable = $item;
                }

                $cobro = $cobroService->registrar($cobrable, [
                    'monto' => $subtotal,
                    'fecha_pago' => $fecha,
                    'metodo_pago_id' => $metodoId,
                    'referencia' => $ref,
                    'comprobante_ids' => $comprobantes,
                    'registrado_por' => $vendedorId,
                    'origen' => 'pos',
                ], recalcular: false);

                $item->update(['cobro_id' => $cobro->id]);
                $total += $subtotal;
            }

            $venta->update(['total' => round($total, 2)]);
        });

        return redirect()->route('pos.index')->with('success', 'Venta POS registrada correctamente.');
    }
}
