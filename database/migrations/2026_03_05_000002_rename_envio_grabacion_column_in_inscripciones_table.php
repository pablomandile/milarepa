<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE inscripciones
            CHANGE `envioGrabación` `envioGrabacion`
            ENUM('Enviada','Pendiente','No aplica')
            NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE inscripciones
            CHANGE `envioGrabacion` `envioGrabación`
            ENUM('Enviada','Pendiente','No aplica')
            NULL
        ");
    }
};

