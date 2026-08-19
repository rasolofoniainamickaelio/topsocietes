<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `routes` centralise indexabilité, canonicalisation, rattachement au
 * sitemap et détection des collisions de slug en un seul endroit plutôt
 * que dispersés sur 8 modèles (décision structurante n°3).
 *
 * `entity_type`/`entity_id` sont nullables : pour `company`/`city`/
 * `district`/`admin_division`/`activity`, ils pointent l'entité
 * correspondante ; pour `activity_city`/`sector_geo` (pages combinatoires
 * activité × territoire), la ligne `city_activity_contents` sert
 * d'entité quand elle existe, mais le prompt ne définit pas de table
 * "page combinatoire" dédiée — la ligne `routes` + sa
 * `page_publication_decision` restent la source de vérité même sans
 * entité concrète rattachée. Décision documentée, pas d'hypothèse
 * silencieuse.
 *
 * Détection et aplatissement des boucles/chaînes de redirection : règle
 * métier explicitement exigée par le prompt, non exprimable en contrainte
 * SQL (nécessiterait de suivre une chaîne arbitraire) — à implémenter
 * comme Action lors de l'écriture d'une nouvelle redirection (Phase 10),
 * documentée ici et dans docs/DATABASE.md, pas dans cette migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_shards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->integer('index');
            $table->integer('url_count')->default(0);
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->boolean('is_stale')->default(true);
            $table->timestamps();

            $table->unique(['country_id', 'type', 'index'], 'uq_sitemap_shards_country_type_index');
        });

        DB::statement('ALTER TABLE sitemap_shards ADD CONSTRAINT chk_sitemap_shards_url_count CHECK (url_count <= 50000)');

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('path');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('page_type');
            $table->foreignId('canonical_route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->boolean('is_indexable')->default(true);
            $table->string('noindex_reason')->nullable();
            $table->decimal('priority', 2, 1)->default(0.5);
            $table->string('changefreq')->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->foreignId('sitemap_shard_id')->nullable()->constrained('sitemap_shards')->nullOnDelete();
            $table->timestamps();

            $table->unique(['country_id', 'path'], 'uq_routes_country_path');
            $table->index(['entity_type', 'entity_id'], 'idx_routes_entity');
            $table->index('page_type', 'idx_routes_page_type');
        });

        DB::statement('ALTER TABLE routes ADD CONSTRAINT chk_routes_priority CHECK (priority BETWEEN 0 AND 1)');

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('from_path');
            $table->string('to_path');
            $table->smallInteger('status_code')->default(301);
            $table->string('reason');
            $table->integer('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['country_id', 'from_path'], 'uq_redirects_country_from_path');
        });

        DB::statement('ALTER TABLE redirects ADD CONSTRAINT chk_redirects_status_code CHECK (status_code IN (301, 410))');

        Schema::create('page_publication_rules', function (Blueprint $table) {
            $table->id();
            $table->string('page_type');
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->integer('min_companies')->default(0);
            $table->integer('min_facts')->default(0);
            $table->integer('min_content_sections')->default(0);
            $table->integer('min_word_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // country_id nullable = règle globale par défaut pour ce page_type :
        // deux index partiels (comme pour activity_contents, Domaine E) plutôt
        // qu'un UNIQUE composite classique inefficace face aux NULL.
        DB::statement('CREATE UNIQUE INDEX uq_page_publication_rules_type_country ON page_publication_rules (page_type, country_id) WHERE country_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX uq_page_publication_rules_type_global ON page_publication_rules (page_type) WHERE country_id IS NULL');

        Schema::create('page_publication_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
            $table->integer('score')->nullable();
            $table->string('decision');
            $table->jsonb('reasons')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->index('route_id', 'idx_page_publication_decisions_route');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_publication_decisions');
        Schema::dropIfExists('page_publication_rules');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('sitemap_shards');
    }
};
