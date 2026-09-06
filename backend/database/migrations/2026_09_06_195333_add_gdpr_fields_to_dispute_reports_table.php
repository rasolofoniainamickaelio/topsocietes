<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RGPD (Phase 22) : `deleted_at` permet à un modérateur d'archiver un
 * signalement sans le détruire (la suppression Filament devient un
 * soft-delete transparent dès que la colonne existe). `anonymized_at` est
 * distinct : il marque le passage de la purge planifiée qui vide les
 * champs personnels (`reporter_*`, `ip_hash`, `evidence_path`) d'un
 * signalement clos ancien — jamais posé par une suppression manuelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispute_reports', function (Blueprint $table) {
            $table->softDeletes();
            $table->timestamp('anonymized_at')->nullable();
        });

        Schema::table('dispute_events', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('dispute_reports', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'anonymized_at']);
        });

        Schema::table('dispute_events', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
