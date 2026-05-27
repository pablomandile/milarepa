<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresia_usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('membresia_usuario', 'suscripcion')) {
                $table->boolean('suscripcion')->default(false)->after('membresia_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membresia_usuario', function (Blueprint $table) {
            if (Schema::hasColumn('membresia_usuario', 'suscripcion')) {
                $table->dropColumn('suscripcion');
            }
        });
    }
};
