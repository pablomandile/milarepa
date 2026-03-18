<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresia_usuario', function (Blueprint $table) {
            $table->date('envioActOnline')->nullable()->after('envioInfoTk');
        });
    }

    public function down(): void
    {
        Schema::table('membresia_usuario', function (Blueprint $table) {
            $table->dropColumn('envioActOnline');
        });
    }
};
