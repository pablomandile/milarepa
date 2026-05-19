<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_actividad_id')->constrained('tipos_actividad')->onDelete('cascade');
            $table->string('nombre', 80);
            $table->foreignId('descripcion_id')->nullable()->constrained('descripciones')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->onDelete('cascade');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->dateTime('pagoAmticipado')->nullable();
            $table->foreignId('entidad_id')->constrained('entidades')->onDelete('cascade');
            $table->foreignId('lugar_id')->nullable();
            $table->foreignId('disponibilidad_id')->nullable()->constrained('disponibilidades')->onDelete('cascade');
            $table->foreignId('modalidad_id')->constrained('modalidades')->onDelete('cascade');
            $table->foreignId('esquema_precio_id')->constrained('esquema_precios')->onDelete('cascade');
            $table->foreignId('esquema_descuento_id')->nullable()->constrained('esquema_descuentos')->onDelete('cascade');
            $table->string('link_web')->nullable();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->onDelete('cascade');
            $table->foreignId('programa_id')->nullable()->constrained('programas')->onDelete('cascade');
            $table->boolean('estado')->default(true);
            $table->foreignId('botonpago_id')->nullable();
            $table->foreignId('grabacion_id')->nullable()->constrained('grabaciones')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
