<?php

use App\Models\MetodoPago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cobros')) {
            Schema::create('cobros', function (Blueprint $table) {
                $table->id();
                $table->morphs('cobrable'); // cobrable_type + cobrable_id + índice compuesto
                $table->decimal('monto', 10, 2);
                $table->foreignId('moneda_id')->nullable()->constrained('monedas')->nullOnDelete();
                $table->date('fecha_pago')->nullable();
                $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();
                $table->string('referencia')->nullable();
                $table->foreignId('comprobante_id')->nullable()->constrained('imagenes')->nullOnDelete();
                $table->text('observaciones')->nullable();
                $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->string('origen', 20)->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('fecha_pago');
                $table->index('origen');
            });
        }

        // Métodos de pago que usan las membresías y hoy faltan en el catálogo (el resto ya existe).
        foreach ([
            ['nombre' => 'Suscripción', 'descripcion' => 'Débito automático / suscripción', 'tipo_de_pago' => 'Online'],
            ['nombre' => 'Otro', 'descripcion' => 'Otro medio de pago', 'tipo_de_pago' => 'Presencial'],
        ] as $metodo) {
            MetodoPago::firstOrCreate(
                ['nombre' => $metodo['nombre']],
                ['descripcion' => $metodo['descripcion'], 'tipo_de_pago' => $metodo['tipo_de_pago']]
            );
        }

        $permisos = [
            'create cobros',
            'read cobros',
            'update cobros',
            'delete cobros',
        ];
        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permisos);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');

        Permission::whereIn('name', [
            'create cobros',
            'read cobros',
            'update cobros',
            'delete cobros',
        ])->delete();
    }
};
