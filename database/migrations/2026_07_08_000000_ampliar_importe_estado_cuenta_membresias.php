<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El importe de un pago en pesos puede superar el máximo de decimal(8,2)
     * (999.999,99). Se amplía a decimal(10,2) para acompañar montos altos,
     * consistente con las demás columnas `importe` de la app.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `estado_cuenta_membresias` MODIFY `importe` DECIMAL(10,2) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `estado_cuenta_membresias` MODIFY `importe` DECIMAL(8,2) NOT NULL');
    }
};
