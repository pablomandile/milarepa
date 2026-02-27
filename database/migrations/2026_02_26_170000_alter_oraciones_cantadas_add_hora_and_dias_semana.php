<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('oraciones_cantadas')) {
            return;
        }

        if (!Schema::hasColumn('oraciones_cantadas', 'dias_semana')) {
            DB::statement("ALTER TABLE `oraciones_cantadas` ADD COLUMN `dias_semana` JSON NULL AFTER `dia`");
        }

        if (!Schema::hasColumn('oraciones_cantadas', 'hora')) {
            DB::statement("ALTER TABLE `oraciones_cantadas` ADD COLUMN `hora` TIME NULL AFTER `dias_semana`");
        }

        if (Schema::hasColumn('oraciones_cantadas', 'dia')) {
            DB::statement("ALTER TABLE `oraciones_cantadas` MODIFY `dia` TINYINT UNSIGNED NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('oraciones_cantadas')) {
            return;
        }

        if (Schema::hasColumn('oraciones_cantadas', 'hora')) {
            DB::statement("ALTER TABLE `oraciones_cantadas` DROP COLUMN `hora`");
        }

        if (Schema::hasColumn('oraciones_cantadas', 'dias_semana')) {
            DB::statement("ALTER TABLE `oraciones_cantadas` DROP COLUMN `dias_semana`");
        }
    }
};
