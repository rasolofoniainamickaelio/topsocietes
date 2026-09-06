<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 20 : deux requêtes fréquentes de la Phase 16/17 n'étaient couvertes
 * par aucun index.
 *
 * - `CreateRedirectAction::resolveFinalTarget()`/le repointage des chaînes
 *   filtrent sur `to_path`, jamais indexé (seul `from_path` l'était, via la
 *   contrainte unique).
 * - `GenerateSitemapsAction` filtre systématiquement
 *   `(country_id, page_type, is_indexable)` ensemble ; `idx_routes_page_type`
 *   ne couvrait que `page_type` seul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->index(['country_id', 'to_path'], 'idx_redirects_country_to_path');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->index(['country_id', 'page_type', 'is_indexable'], 'idx_routes_country_type_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex('idx_routes_country_type_indexable');
        });

        Schema::table('redirects', function (Blueprint $table) {
            $table->dropIndex('idx_redirects_country_to_path');
        });
    }
};
