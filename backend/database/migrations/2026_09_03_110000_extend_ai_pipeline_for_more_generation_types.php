<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phases 10-11 : les types de génération manquants (activité, croisé
 * ville×activité, réécriture/résumé/contextualisation) et leurs besoins de
 * traçabilité/contrôle.
 *
 * - `ai_generation_jobs.activity_id`/`sector_id` : seul le couple
 *   ville×activité (ou ville×secteur) n'a pas de modèle Eloquent propre à
 *   cibler comme `target` (`target_type`/`target_id` pointe alors sur la
 *   ville) — ces colonnes portent l'activité OU le secteur manquant, jamais
 *   les deux (même XOR que `city_activity_contents`). Toujours nulles pour
 *   les autres types de génération, où `target` suffit.
 * - `ai_generation_jobs.mode` : distingue une création (`create`, valeur par
 *   défaut) d'une transformation d'un contenu déjà existant (`rewrite`,
 *   `summarize`, `contextualize`), qui ne crée jamais de nouvelle ligne.
 * - `reviewed_by`/`reviewed_at` sur les 4 tables de contenu territorial :
 *   capture l'identité de l'humain qui a validé un contenu en statut
 *   `review`, plutôt qu'un simple edit du champ `status` sans traçabilité.
 * - `ai_budgets` : plafond de dépense mensuel par pays (Phase 10, "gestion
 *   des coûts... quotas").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generation_jobs', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('target_id')->constrained('activities')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->after('activity_id')->constrained('sectors')->nullOnDelete();
            $table->string('mode')->default('create')->after('section');
        });

        foreach (['city_contents', 'district_contents', 'admin_division_contents', 'activity_contents', 'city_activity_contents'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
            });
        }

        Schema::create('ai_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7); // 'YYYY-MM'
            $table->integer('max_cost_cents');
            $table->integer('spent_cents')->default(0);
            $table->timestamps();

            $table->unique(['country_id', 'period'], 'uq_ai_budgets_country_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_budgets');

        foreach (['city_contents', 'district_contents', 'admin_division_contents', 'activity_contents', 'city_activity_contents'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('reviewed_by');
                $table->dropColumn('reviewed_at');
            });
        }

        Schema::table('ai_generation_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sector_id');
            $table->dropConstrainedForeignId('activity_id');
            $table->dropColumn('mode');
        });
    }
};
