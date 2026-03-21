<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('inventario_entidad_libro')) {
            Schema::create('inventario_entidad_libro', function (Blueprint $table) {
                $table->id();
                $table->foreignId('entidad_id')->constrained('entidades')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('libro_id')->constrained('libros')->cascadeOnUpdate()->cascadeOnDelete();
                $table->unsignedInteger('cantidad')->default(0);
                $table->timestamps();

                $table->unique(['entidad_id', 'libro_id'], 'inventario_entidad_libro_entidad_libro_unique');
            });
        }

        if (Schema::hasTable('inventario_libros')) {
            $entidadPrincipalId = DB::table('entidades')
                ->where('entidad_principal', true)
                ->value('id');

            if ($entidadPrincipalId) {
                $registros = DB::table('inventario_libros')
                    ->select('libro_id', 'cantidad', 'created_at', 'updated_at')
                    ->get();

                foreach ($registros as $registro) {
                    DB::table('inventario_entidad_libro')->updateOrInsert(
                        [
                            'entidad_id' => $entidadPrincipalId,
                            'libro_id' => $registro->libro_id,
                        ],
                        [
                            'cantidad' => $registro->cantidad,
                            'created_at' => $registro->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            Schema::dropIfExists('inventario_libros');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('inventario_libros')) {
            Schema::create('inventario_libros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('libro_id')->constrained('libros')->cascadeOnUpdate()->cascadeOnDelete();
                $table->unsignedInteger('cantidad')->default(0);
                $table->timestamps();

                $table->unique('libro_id');
            });
        }

        $entidadPrincipalId = DB::table('entidades')
            ->where('entidad_principal', true)
            ->value('id');

        if ($entidadPrincipalId && Schema::hasTable('inventario_entidad_libro')) {
            $registrosPrincipal = DB::table('inventario_entidad_libro')
                ->where('entidad_id', $entidadPrincipalId)
                ->select('libro_id', 'cantidad', 'created_at', 'updated_at')
                ->get();

            foreach ($registrosPrincipal as $registro) {
                DB::table('inventario_libros')->updateOrInsert(
                    ['libro_id' => $registro->libro_id],
                    [
                        'cantidad' => $registro->cantidad,
                        'created_at' => $registro->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        Schema::dropIfExists('inventario_entidad_libro');
    }
};
