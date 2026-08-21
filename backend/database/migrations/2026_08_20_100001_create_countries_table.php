<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel des 6 pays cibles. Toute variation nationale (libellés de
 * divisions administratives, format d'identifiant légal, nomenclature
 * d'activité, sources open data activées) est portée en JSONB ici plutôt
 * que par des tables ou des enums dupliqués par pays (voir CLAUDE.md §6.6
 * et la décision structurante "multi-pays par configuration").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->char('iso_alpha2', 2);
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('default_locale', 10);
            $table->char('currency', 3);
            $table->string('timezone');
            $table->boolean('is_active')->default(false);
            $table->jsonb('admin_level_labels')->nullable();
            $table->jsonb('identifier_config')->nullable();
            $table->string('activity_nomenclature_code')->nullable();
            $table->jsonb('url_patterns')->nullable();
            $table->jsonb('source_config')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
