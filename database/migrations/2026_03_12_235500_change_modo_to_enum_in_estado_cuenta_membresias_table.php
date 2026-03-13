<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $modos = [
        'Efectivo',
        'Transferencia',
        'Suscripción',
        'Tarjeta Crédito',
        'Tarjeta Débito',
        'Otro',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('estado_cuenta_membresias', 'modo')) {
            return;
        }

        DB::table('estado_cuenta_membresias')
            ->whereNotNull('modo')
            ->whereNotIn('modo', $this->modos)
            ->update(['modo' => 'Otro']);

        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $enumValues = implode("','", array_map(
            static fn (string $value) => str_replace("'", "''", $value),
            $this->modos
        ));

        DB::statement(
            "ALTER TABLE estado_cuenta_membresias MODIFY COLUMN modo ENUM('{$enumValues}') NULL"
        );
    }

    public function down(): void
    {
        if (!Schema::hasColumn('estado_cuenta_membresias', 'modo')) {
            return;
        }

        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE estado_cuenta_membresias MODIFY COLUMN modo VARCHAR(50) NULL"
        );
    }
};
