<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lugares')) {
            return;
        }

        Schema::table('lugares', function (Blueprint $table) {
            if (Schema::hasColumn('lugares', 'abreviacion')) {
                $table->dropColumn('abreviacion');
            }
            if (Schema::hasColumn('lugares', 'facebook_uri')) {
                $table->dropColumn('facebook_uri');
            }
            if (Schema::hasColumn('lugares', 'twitter_uri')) {
                $table->dropColumn('twitter_uri');
            }
            if (Schema::hasColumn('lugares', 'youtube_uri')) {
                $table->dropColumn('youtube_uri');
            }
            if (Schema::hasColumn('lugares', 'spotify_uri')) {
                $table->dropColumn('spotify_uri');
            }
            if (Schema::hasColumn('lugares', 'entidad_principal')) {
                $table->dropColumn('entidad_principal');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('lugares')) {
            return;
        }

        Schema::table('lugares', function (Blueprint $table) {
            if (!Schema::hasColumn('lugares', 'abreviacion')) {
                $table->string('abreviacion', 15)->nullable()->after('descripcion');
            }
            if (!Schema::hasColumn('lugares', 'facebook_uri')) {
                $table->string('facebook_uri', 255)->nullable()->after('instagram_uri');
            }
            if (!Schema::hasColumn('lugares', 'twitter_uri')) {
                $table->string('twitter_uri', 255)->nullable()->after('facebook_uri');
            }
            if (!Schema::hasColumn('lugares', 'youtube_uri')) {
                $table->string('youtube_uri', 255)->nullable()->after('twitter_uri');
            }
            if (!Schema::hasColumn('lugares', 'spotify_uri')) {
                $table->string('spotify_uri', 255)->nullable()->after('youtube_uri');
            }
            if (!Schema::hasColumn('lugares', 'entidad_principal')) {
                $table->boolean('entidad_principal')->default(false)->after('email2');
            }
        });
    }
};

