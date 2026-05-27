<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_estudios', function (Blueprint $table) {
            if (!Schema::hasColumn('programa_estudios', 'compromisos_pdf')) {
                $table->string('compromisos_pdf', 500)->nullable()->after('descripcion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('programa_estudios', function (Blueprint $table) {
            if (Schema::hasColumn('programa_estudios', 'compromisos_pdf')) {
                $table->dropColumn('compromisos_pdf');
            }
        });
    }
};
