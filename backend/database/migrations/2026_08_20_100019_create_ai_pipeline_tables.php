<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pipeline IA : `ai_prompts` (versionnés, un seul actif par clé) →
 * `ai_generation_jobs` (batch, jamais synchrone à l'affichage — CLAUDE.md
 * §6.1) → `ai_generation_logs` (payload de faits exact envoyé + sortie
 * brute + rapport de validation, pour audit). Aucune table de ce domaine
 * n'est lue par le front public.
 *
 * Referme les références en avant laissées ouvertes par les Domaines C et
 * E maintenant que `ai_generation_jobs` existe : `companies.
 * about_generation_id` et les 4 colonnes `generation_id` des tables de
 * contenu. `nullOnDelete()` partout : la suppression d'un job ne doit
 * jamais entraîner la suppression du contenu déjà publié, seulement la
 * perte du lien de traçabilité vers ce job précis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('version');
            $table->string('scope');
            $table->text('system_prompt');
            $table->text('user_template');
            $table->string('model');
            $table->jsonb('parameters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'version'], 'uq_ai_prompts_key_version');
        });

        DB::statement('CREATE UNIQUE INDEX uq_ai_prompts_key_active ON ai_prompts (key) WHERE is_active');

        Schema::create('ai_generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('section');
            $table->string('locale', 10);
            $table->foreignId('prompt_id')->constrained('ai_prompts')->restrictOnDelete();
            $table->string('model');
            $table->string('provider');
            $table->string('status');
            $table->smallInteger('attempts')->default(0);
            $table->string('batch_reference')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->integer('cost_cents')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at'], 'idx_ai_jobs_status_scheduled');
            $table->index(['target_type', 'target_id'], 'idx_ai_jobs_target');
        });

        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_job_id')->constrained('ai_generation_jobs')->cascadeOnDelete();
            $table->jsonb('input_payload')->nullable();
            $table->text('raw_output')->nullable();
            $table->jsonb('validation_report')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('about_generation_id', 'fk_companies_about_generation')
                ->references('id')->on('ai_generation_jobs')
                ->nullOnDelete();
        });

        Schema::table('city_contents', function (Blueprint $table) {
            $table->foreign('generation_id', 'fk_city_contents_generation')
                ->references('id')->on('ai_generation_jobs')
                ->nullOnDelete();
        });

        Schema::table('district_contents', function (Blueprint $table) {
            $table->foreign('generation_id', 'fk_district_contents_generation')
                ->references('id')->on('ai_generation_jobs')
                ->nullOnDelete();
        });

        Schema::table('activity_contents', function (Blueprint $table) {
            $table->foreign('generation_id', 'fk_activity_contents_generation')
                ->references('id')->on('ai_generation_jobs')
                ->nullOnDelete();
        });

        Schema::table('city_activity_contents', function (Blueprint $table) {
            $table->foreign('generation_id', 'fk_city_activity_contents_generation')
                ->references('id')->on('ai_generation_jobs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('city_activity_contents', function (Blueprint $table) {
            $table->dropForeign('fk_city_activity_contents_generation');
        });

        Schema::table('activity_contents', function (Blueprint $table) {
            $table->dropForeign('fk_activity_contents_generation');
        });

        Schema::table('district_contents', function (Blueprint $table) {
            $table->dropForeign('fk_district_contents_generation');
        });

        Schema::table('city_contents', function (Blueprint $table) {
            $table->dropForeign('fk_city_contents_generation');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign('fk_companies_about_generation');
        });

        Schema::dropIfExists('ai_generation_logs');
        Schema::dropIfExists('ai_generation_jobs');
        Schema::dropIfExists('ai_prompts');
    }
};
