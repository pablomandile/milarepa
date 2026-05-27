<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa_grabacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_estudio_id')->constrained('programa_estudios')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('archivo', 500);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();
        });

        $permisos = [
            'create programa-grabaciones',
            'read programa-grabaciones',
            'update programa-grabaciones',
            'delete programa-grabaciones',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permisos);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_grabacion');

        Permission::whereIn('name', [
            'create programa-grabaciones',
            'read programa-grabaciones',
            'update programa-grabaciones',
            'delete programa-grabaciones',
        ])->delete();
    }
};
