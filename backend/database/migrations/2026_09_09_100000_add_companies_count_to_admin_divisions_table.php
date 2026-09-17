<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligne `admin_divisions` sur `cities`/`districts`/`activities`/`sectors` :
 * un compteur pré-calculé plutôt qu'un `count()` en direct à chaque
 * évaluation de publication (Phase 13). Table déjà peuplée en développement,
 * ajout de colonne nullable/à valeur par défaut, aucune donnée existante
 * modifiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_divisions', function (Blueprint $table) {
            $table->integer('companies_count')->default(0)->after('path');
            $table->timestamp('counts_updated_at')->nullable()->after('companies_count');
        });
    }

    public function down(): void
    {
        Schema::table('admin_divisions', function (Blueprint $table) {
            $table->dropColumn(['companies_count', 'counts_updated_at']);
        });
    }
};
