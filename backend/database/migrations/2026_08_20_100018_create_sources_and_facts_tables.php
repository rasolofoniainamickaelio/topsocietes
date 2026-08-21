<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chaîne de confiance anti-hallucination : `sources` → `source_documents`
 * (donnée brute ET normalisée conservées) → `facts` (`is_usable` calculé)
 * → `content_source_links` (traçabilité a posteriori). Le générateur IA
 * ne doit pouvoir piocher que des `facts` avec `is_usable = true`
 * (CLAUDE.md §6.7) — c'est cette table qui rend la contrainte vérifiable.
 *
 * Seuil de `is_usable` (confidence_score >= 60) : aucune valeur n'était
 * donnée dans le prompt, hypothèse à valider avant mise en prod
 * (documentée dans docs/DATABASE.md). Modifier ce seuil nécessite une
 * migration puisqu'il s'agit d'une colonne générée STORED.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->string('license')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->smallInteger('reliability_score')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('source_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->restrictOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->text('url')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->jsonb('normalized_payload')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->char('hash', 64)->nullable();
            $table->smallInteger('http_status')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'idx_source_documents_subject');
            $table->index('source_id', 'idx_source_documents_source');
        });

        Schema::create('facts', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->jsonb('value_json')->nullable();
            $table->foreignId('source_id')->constrained('sources')->restrictOnDelete();
            $table->text('source_url')->nullable();
            $table->timestamp('date_retrieved')->nullable();
            $table->smallInteger('confidence_score')->default(0);
            $table->smallInteger('corroboration_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'key'], 'idx_facts_subject_key');
            $table->index('source_id', 'idx_facts_source');
        });

        DB::statement('ALTER TABLE facts ADD CONSTRAINT chk_facts_confidence_score CHECK (confidence_score BETWEEN 0 AND 100)');
        DB::statement('ALTER TABLE facts ADD COLUMN is_usable boolean GENERATED ALWAYS AS (confidence_score >= 60 AND corroboration_count >= 1) STORED');
        DB::statement('CREATE INDEX idx_facts_usable_subject ON facts (subject_type, subject_id) WHERE is_usable');

        Schema::create('content_source_links', function (Blueprint $table) {
            $table->id();
            $table->string('content_type');
            $table->unsignedBigInteger('content_id');
            $table->foreignId('fact_id')->nullable()->constrained('facts')->restrictOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('source_documents')->restrictOnDelete();
            $table->timestamps();

            $table->index(['content_type', 'content_id'], 'idx_content_source_links_content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_source_links');
        Schema::dropIfExists('facts');
        Schema::dropIfExists('source_documents');
        Schema::dropIfExists('sources');
    }
};
