<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscripciones_clases', function (Blueprint $table) {
            if (!Schema::hasColumn('inscripciones_clases', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('clase_id');
            }
        });

        if (Schema::hasColumn('inscripciones_clases', 'usuario_id') && Schema::hasColumn('inscripciones_clases', 'user_id')) {
            DB::table('inscripciones_clases')
                ->whereNull('user_id')
                ->update([
                    'user_id' => DB::raw('usuario_id'),
                ]);
        }

        Schema::table('inscripciones_clases', function (Blueprint $table) {
            if (Schema::hasColumn('inscripciones_clases', 'usuario_id')) {
                $databaseName = DB::getDatabaseName();
                $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->select('CONSTRAINT_NAME')
                    ->where('TABLE_SCHEMA', $databaseName)
                    ->where('TABLE_NAME', 'inscripciones_clases')
                    ->where('COLUMN_NAME', 'usuario_id')
                    ->whereNotNull('REFERENCED_TABLE_NAME')
                    ->pluck('CONSTRAINT_NAME');

                foreach ($foreignKeys as $foreignKeyName) {
                    DB::statement("ALTER TABLE `inscripciones_clases` DROP FOREIGN KEY `{$foreignKeyName}`");
                }

                $table->dropColumn('usuario_id');
            }
        });

        Schema::table('inscripciones_clases', function (Blueprint $table) {
            try {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
            } catch (\Throwable $e) {
            }

            if (!Schema::hasColumn('inscripciones_clases', 'nombre_snapshot')) {
                $table->string('nombre_snapshot', 255)->nullable()->after('guest_user_id');
            }

            if (!Schema::hasColumn('inscripciones_clases', 'email_snapshot')) {
                $table->string('email_snapshot', 255)->nullable()->after('nombre_snapshot');
            }

            if (!Schema::hasColumn('inscripciones_clases', 'deleted_at')) {
                $table->softDeletes();
            }

            try {
                $table->unique(['clase_id', 'user_id'], 'inscripciones_clases_clase_user_unique');
            } catch (\Throwable $e) {
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones_clases', function (Blueprint $table) {
            try {
                $table->dropUnique('inscripciones_clases_clase_user_unique');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('inscripciones_clases', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('inscripciones_clases', 'email_snapshot')) {
                $table->dropColumn('email_snapshot');
            }

            if (Schema::hasColumn('inscripciones_clases', 'nombre_snapshot')) {
                $table->dropColumn('nombre_snapshot');
            }

            if (Schema::hasColumn('inscripciones_clases', 'user_id') && !Schema::hasColumn('inscripciones_clases', 'usuario_id')) {
                $table->unsignedBigInteger('usuario_id')->nullable()->after('clase_id');
            }
        });

        if (Schema::hasColumn('inscripciones_clases', 'usuario_id') && Schema::hasColumn('inscripciones_clases', 'user_id')) {
            DB::table('inscripciones_clases')
                ->whereNull('usuario_id')
                ->update([
                    'usuario_id' => DB::raw('user_id'),
                ]);
        }

        Schema::table('inscripciones_clases', function (Blueprint $table) {
            if (Schema::hasColumn('inscripciones_clases', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('inscripciones_clases', 'usuario_id')) {
                try {
                    $table->foreign('usuario_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
                } catch (\Throwable $e) {
                }

                try {
                    $table->index(['clase_id', 'usuario_id']);
                } catch (\Throwable $e) {
                }
            }
        });
    }
};
