<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades');
            $table->foreignId('user_id')->constrained('users');
            $table->string('membresia');
            $table->decimal('precioGeneral', 10, 2);
            $table->decimal('montoActividad', 10, 2)->default(0);
            $table->decimal('montoGrabacion', 10, 2)->nullable();
            $table->decimal('montoTransporte', 10, 2)->nullable();
            $table->decimal('montoComidas', 10, 2)->nullable();
            $table->decimal('montoapagar', 10, 2);
            $table->enum('pago', ['Saldado', 'Pendiente', 'Parcial']);
            $table->enum('estado', ['Registrada', 'Confirmada'])->default('Registrada');
            $table->enum('envioLinkStream', ['Enviado', 'Pendiente', 'No aplica']);
            $table->enum('envioRegistro', ['Enviada', 'Pendiente'])->default('Pendiente');
            $table->enum('envioConfirmacion', ['Enviada', 'Pendiente'])->default('Pendiente');
            $table->enum('envioGrabacion', ['Enviada', 'Pendiente', 'No aplica'])->nullable();
            $table->enum('asistencia', ['Presente', 'Ausente', 'Pendiente']);
            $table->boolean('online');
            $table->unsignedBigInteger('hospedaje_id')->nullable();
            $table->unsignedBigInteger('comida_id')->nullable();
            $table->unsignedBigInteger('transporte_id')->nullable();
            $table->timestamp('auditoria_fecha')->nullable();
            $table->foreignId('auditor')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guest_user_id')->nullable()->constrained('guest_users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
