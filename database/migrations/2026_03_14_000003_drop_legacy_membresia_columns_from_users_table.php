<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['membresia_id']);
            $table->dropColumn([
                'membresia_id',
                'membresia_inscripcion_fecha',
                'membresia_online',
                'membresia_online_motivo',
                'info_tarjetas_kadampa',
                'envioInfoTk',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('membresia_id')->nullable()->constrained('membresias')->nullOnDelete();
            $table->date('membresia_inscripcion_fecha')->nullable();
            $table->boolean('membresia_online')->default(false);
            $table->string('membresia_online_motivo', 255)->nullable();
            $table->boolean('info_tarjetas_kadampa')->default(false);
            $table->dateTime('envioInfoTk')->nullable();
        });
    }
};
