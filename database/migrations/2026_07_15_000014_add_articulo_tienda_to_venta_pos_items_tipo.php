<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega el valor 'articulo_tienda' al enum `tipo` de venta_pos_items
     * (línea del POS para artículos de la Tienda general). ALTER MODIFY porque
     * el enum es nativo de MySQL: no basta con tocar el array de la migración original.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE venta_pos_items MODIFY tipo ENUM('libro', 'oracion', 'arte', 'otro', 'articulo_tienda', 'inscripcion_actividad', 'inscripcion_clase') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE venta_pos_items MODIFY tipo ENUM('libro', 'oracion', 'arte', 'otro', 'inscripcion_actividad', 'inscripcion_clase') NOT NULL");
    }
};
