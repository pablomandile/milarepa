<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('entidad_id')->nullable()->constrained('entidades')->cascadeOnUpdate()->nullOnDelete();
            $table->string('mes_referencia', 7)->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->cascadeOnUpdate()->nullOnDelete();
            $table->json('dias_semana')->nullable();
            $table->json('titulos_por_fecha')->nullable();
            $table->time('horario_desde');
            $table->time('horario_hasta');
            $table->foreignId('maestro_id')->nullable()->constrained('maestros')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('coordinador_id')->nullable()->constrained('coordinadores')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('esquema_precio_id')->nullable()->constrained('esquema_precios')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidades')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('mostrar_en_calendario')->default(false);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};

