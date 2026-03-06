<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('info_tarjetas_kadampa')->default(false)->after('msgxwapp');
        });

        Schema::table('guest_users', function (Blueprint $table) {
            $table->boolean('info_tarjetas_kadampa')->default(false)->after('accesibilidad_desc');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('info_tarjetas_kadampa');
        });

        Schema::table('guest_users', function (Blueprint $table) {
            $table->dropColumn('info_tarjetas_kadampa');
        });
    }
};

