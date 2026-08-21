<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table centrale des entreprises. `public_id` (ULID) est l'identifiant
 * public stable ; `id` interne n'apparaît jamais dans une URL.
 * `(country_id, national_id)` est la clé de rapprochement pour l'upsert
 * à l'import.
 *
 * Trois colonnes restent volontairement sans contrainte FK ici et seront
 * complétées par un `ALTER TABLE` dans le domaine qui crée la table cible
 * (référence en avant, ordre des domaines A→L oblige) :
 *   - main_establishment_id → establishments (contrainte ajoutée en fin
 *     de la migration `create_establishments_table`, juste après)
 *   - source_batch_id       → import_batches (Domaine H)
 *   - about_generation_id   → ai_generation_jobs (Domaine G)
 *
 * `about_text`/`about_generation_id` : bloc "À propos" généré à partir des
 * seules données de la fiche, stocké directement sur `companies` plutôt
 * que dans une table dédiée (le prompt laissait le choix — voir
 * docs/DATABASE.md). Aucune autre table de contenu propre à l'entreprise
 * n'existe : interdiction structurelle documentée (histoire/tourisme/
 * culture ne sont jamais des contenus d'entreprise, CLAUDE.md §6.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('national_id');
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('slug');
            $table->string('legal_form_code')->nullable();
            $table->string('legal_form_label')->nullable();
            $table->string('status');
            $table->date('created_date')->nullable();
            $table->date('ceased_date')->nullable();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->restrictOnDelete();
            $table->string('activity_code_raw')->nullable();
            $table->string('headcount_range')->nullable();
            $table->unsignedBigInteger('main_establishment_id')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->restrictOnDelete();
            $table->foreignId('admin_division_id')->nullable()->constrained('admin_divisions')->restrictOnDelete();
            $table->string('geocoding_status');
            $table->string('content_status');
            $table->boolean('is_indexable')->default(true);
            $table->unsignedBigInteger('source_batch_id')->nullable();
            $table->text('about_text')->nullable();
            $table->unsignedBigInteger('about_generation_id')->nullable();
            $table->char('data_hash', 64)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('main_establishment_id', 'idx_companies_main_establishment');
            $table->index('source_batch_id', 'idx_companies_source_batch');
            $table->index('about_generation_id', 'idx_companies_about_generation');
        });

        DB::statement('ALTER TABLE companies ADD COLUMN location geography(Point,4326)');
        DB::statement('ALTER TABLE companies ADD COLUMN latitude numeric(10,7) GENERATED ALWAYS AS (ST_Y(location::geometry)) STORED');
        DB::statement('ALTER TABLE companies ADD COLUMN longitude numeric(10,7) GENERATED ALWAYS AS (ST_X(location::geometry)) STORED');

        DB::statement(<<<'SQL'
            ALTER TABLE companies ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                setweight(to_tsvector('french_unaccent', coalesce(legal_name, '')), 'A') ||
                setweight(to_tsvector('french_unaccent', coalesce(trade_name, '')), 'A') ||
                setweight(to_tsvector('french_unaccent', coalesce(national_id, '')), 'B')
            ) STORED
        SQL);

        DB::statement('CREATE UNIQUE INDEX uq_companies_country_national ON companies (country_id, national_id)');
        DB::statement('CREATE INDEX idx_companies_city_activity ON companies (city_id, activity_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_companies_activity_city ON companies (activity_id, city_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX gist_companies_location ON companies USING GIST (location)');
        DB::statement('CREATE INDEX gin_companies_search ON companies USING GIN (search_vector)');
        DB::statement('CREATE INDEX gin_companies_name_trgm ON companies USING GIN (legal_name gin_trgm_ops)');
        DB::statement('CREATE INDEX idx_companies_country_status ON companies (country_id, status) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
