<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membresia_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('membresia_id')->nullable()->constrained('membresias')->nullOnDelete();
            $table->date('membresia_inscripcion_fecha')->nullable();
            $table->boolean('membresia_online')->default(false);
            $table->string('membresia_online_motivo')->nullable();
            $table->boolean('info_tarjetas_kadampa')->default(false);
            $table->dateTime('envioInfoTk')->nullable();
            $table->timestamps();
        });

        DB::table('users')
            ->select([
                'id',
                'membresia_id',
                'membresia_inscripcion_fecha',
                'membresia_online',
                'membresia_online_motivo',
                'info_tarjetas_kadampa',
                'envioInfoTk',
                'created_at',
                'updated_at',
            ])
            ->where(function ($query) {
                $query->whereNotNull('membresia_id')
                    ->orWhereNotNull('membresia_inscripcion_fecha')
                    ->orWhere('membresia_online', true)
                    ->orWhereNotNull('membresia_online_motivo')
                    ->orWhere('info_tarjetas_kadampa', true)
                    ->orWhereNotNull('envioInfoTk');
            })
            ->orderBy('id')
            ->chunkById(300, function ($users) {
                $rows = $users->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'membresia_id' => $user->membresia_id,
                        'membresia_inscripcion_fecha' => $user->membresia_inscripcion_fecha,
                        'membresia_online' => (bool) $user->membresia_online,
                        'membresia_online_motivo' => $user->membresia_online_motivo,
                        'info_tarjetas_kadampa' => (bool) $user->info_tarjetas_kadampa,
                        'envioInfoTk' => $user->envioInfoTk,
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => $user->updated_at ?? now(),
                    ];
                })->all();

                if (!empty($rows)) {
                    DB::table('membresia_usuario')->upsert(
                        $rows,
                        ['user_id'],
                        [
                            'membresia_id',
                            'membresia_inscripcion_fecha',
                            'membresia_online',
                            'membresia_online_motivo',
                            'info_tarjetas_kadampa',
                            'envioInfoTk',
                            'updated_at',
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('membresia_usuario');
    }
};
