# Sistema de Estado de Cuenta para Membresías

## Descripción
Este sistema permite registrar y seguir el estado de pago mensual de los usuarios que se inscriben en membresías. Cuando un usuario se registra en una membresía, se crean automáticamente registros mensuales indicando si cada mes está pagado o impago.

## Características Implementadas

### 1. **Modelo EstadoCuentaMembresia**
Ubicación: `app/Models/EstadoCuentaMembresia.php`

- Relaciones:
  - `belongsTo(User)`: Usuario asociado a la membresía
  - `belongsTo(Membresia)`: Membresía asociada

- Campos:
  - `user_id`: Usuario propietario de la membresía
  - `membresia_id`: Membresía registrada
  - `fecha_pago`: Fecha en que se pagó (nullable)
  - `mes_pagado`: Mes en formato YYYY-MM
  - `importe`: Monto del pago mensual
  - `pagado`: Boolean indicando si está pagado
  - `observaciones`: Notas adicionales

### 2. **Migraciones**
- `2025_01_17_022432_create_estado_cuenta_membresias_table.php`: Crea la tabla inicial
- `2025_01_20_000001_add_pagado_to_estado_cuenta_membresias_table.php`: Agrega el campo `pagado` (boolean)

### 3. **Controlador: EstadoCuentaMembresiasController**
Ubicación: `app/Http/Controllers/EstadoCuentaMembresiasController.php`

Métodos:
- `index()`: Muestra todas las cuentas del usuario autenticado
- `edit()`: Formulario para editar un registro
- `update()`: Actualiza el estado de pago, fecha y observaciones
- `destroy()`: Elimina un registro
- `crearEstadosCuenta()`: Método auxiliar que genera los registros mensuales automáticamente

**Validaciones:**
- `pagado`: Boolean requerido
- `fecha_pago`: Fecha opcional
- `observaciones`: Texto opcional, máximo 255 caracteres

### 4. **Controlador: RegistroMembresiasController (Actualizado)**
Ubicación: `app/Http/Controllers/RegistroMembresiasController.php`

Cambios:
- `index()`: Ahora carga las relaciones `entidad` y `esquemaPrecioMembresias`
- `store()`: Implementada la lógica para crear automáticamente los registros mensuales de estado de cuenta

Proceso de registro:
1. Usuario selecciona una membresía
2. Especifica fecha de inicio y cantidad de meses
3. Opcionalmente ingresa un importe (sino usa el precio configurado)
4. Sistema crea automáticamente registros mensuales con `pagado = false`

### 5. **Vistas Vue3**

#### `resources/js/Pages/RegistroMembresias/Index.vue`
- Grid de membresías disponibles
- Muestra nombre, descripción, entidad y precio
- Botón "Inscribirme" para cada membresía

#### `resources/js/Pages/RegistroMembresias/Create.vue`
- Formulario para registrar una membresía
- Campos: Fecha de inicio, Cantidad de meses, Importe (opcional)
- Resumen visual del período de suscripción
- Validación en el cliente

#### `resources/js/Pages/EstadoCuentaMembresias/Index.vue`
- Tabla con todos los registros del usuario
- Columnas: Membresía, Mes, Importe, Estado, Fecha de Pago, Observaciones
- Indicadores visuales: Pagado (verde), Impago (rojo)
- Acciones: Editar, Eliminar
- Paginación de 10 registros por página

#### `resources/js/Pages/EstadoCuentaMembresias/Edit.vue`
- Formulario para editar un registro
- Permite cambiar estado de pago, fecha y observaciones
- Información de la membresía (solo lectura)
- Botones: Guardar, Cancelar

### 6. **Relaciones de Modelos**

**User.php:**
```php
public function estadoCuentasMembresias()
{
    return $this->hasMany(EstadoCuentaMembresia::class, 'user_id');
}
```

**Membresia.php:**
```php
public function estadoCuenta()
{
    return $this->hasMany(EstadoCuentaMembresia::class, 'membresia_id');
}

public function esquemaPrecioMembresias()
{
    return $this->hasMany(EsquemaPrecioMembresia::class, 'membresia_id');
}
```

### 7. **Rutas**
En `routes/web.php`:

```php
use App\Http\Controllers\EstadoCuentaMembresiasController;

// Registro de membresías
Route::resource('/registromembresias', RegistroMembresiasController::class);

// Estado de cuenta (sin create ni store)
Route::resource('/estado-cuenta-membresias', EstadoCuentaMembresiasController::class, [
    'parameters' => ['estado-cuenta-membresias' => 'estadoCuentaMembresia'],
    'except' => ['create', 'store']
]);
```

### 8. **Menú de Navegación**
En `AppLayout.vue`, bajo la sección "Membresías":
- "Membresías Disponibles" → `/registromembresias`
- "Mi Estado de Cuenta" → `/estado-cuenta-membresias`

## Flujo de Uso

### Para Registrar una Membresía:
1. Usuario navega a "Mi Membresía" → "Membresías Disponibles"
2. Selecciona una membresía disponible
3. Hace clic en "Inscribirme"
4. Llena el formulario:
   - Fecha de inicio
   - Cantidad de meses
   - Importe (opcional)
5. Confirma el registro
6. El sistema crea automáticamente los registros mensuales en estado "Impago"

### Para Ver el Estado de Cuenta:
1. Usuario navega a "Mi Membresía" → "Mi Estado de Cuenta"
2. Ve una tabla con todos sus pagos mensuales
3. Puede editar cada registro (marcar como pagado, agregar fecha de pago, notas)
4. Puede eliminar registros si es necesario

### Para Editar un Registro:
1. Hace clic en el icono de editar (lápiz)
2. Cambia: estado de pago, fecha de pago, observaciones
3. Guarda los cambios
4. Sistema redirige a la lista actualizada

## Características Técnicas

- **Prevención de Duplicados:** El sistema verifica si ya existe un registro para ese usuario, membresía y mes
- **Validación de Fechas:** Las fechas se validan correctamente en el servidor
- **Confirmación de Eliminación:** Usa SweetAlert2 para confirmar antes de eliminar
- **Responsividad:** Las vistas se adaptan a diferentes tamaños de pantalla
- **Iconografía:** Usa FontAwesome para iconos consistentes
- **Paginación:** La lista de estado de cuenta está paginada

## Instalación y Configuración

1. Las migraciones se ejecutan con `php artisan migrate`
2. Las rutas están automáticamente registradas
3. El menú se actualiza automáticamente en AppLayout.vue
4. Compilar con `npm run build` para producción

## Estructura de Base de Datos

Tabla: `estado_cuenta_membresias`
```
- id (PK)
- user_id (FK → users)
- membresia_id (FK → membresias)
- fecha_pago (date, nullable)
- mes_pagado (string: YYYY-MM)
- importe (decimal)
- pagado (boolean, default: false)
- observaciones (string)
- created_at (timestamp)
- updated_at (timestamp)
```

## Próximas Mejoras Sugeridas

- Generar reportes de pagos por usuario
- Exportar estado de cuenta a PDF
- Enviar recordatorios por email cuando hay pagos pendientes
- Dashboard con resumen de membresías activas
- Historial de cambios en pagos
- Integración con sistema de pagos
