<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maestros', function (Blueprint $table) {
            $table->text('sobre_maestro')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('maestros', function (Blueprint $table) {
            $table->dropColumn('sobre_maestro');
        });
    }
};
