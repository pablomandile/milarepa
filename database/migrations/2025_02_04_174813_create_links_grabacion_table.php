<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('links_grabacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('link');
            $table->foreignId('grabacion_id')->constrained('grabaciones')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links_grabacion');
    }
};
