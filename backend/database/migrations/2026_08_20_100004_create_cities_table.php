<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Communes. `location` est NOT NULL : contrairement aux entreprises
 * (géocodées progressivement à l'import), une commune vient toujours
 * d'un référentiel officiel qui fournit ses coordonnées.
 *
 * `companies_count`/`has_local_content` sont des compteurs dénormalisés,
 * mis à jour par job planifié — jamais par `withCount()` à l'affichage
 * sur une table à plusieurs millions de lignes liées (voir CLAUDE.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_division_id')->nullable()->constrained('admin_divisions')->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('slug');
            $table->integer('population')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->integer('altitude')->nullable();
            $table->integer('companies_count')->default(0);
            $table->timestamp('counts_updated_at')->nullable();
            $table->boolean('has_local_content')->default(false);
            $table->timestamps();

            $table->unique(['country_id', 'code'], 'uq_cities_country_code');
            $table->unique(['country_id', 'slug'], 'uq_cities_country_slug');
            $table->index('admin_division_id', 'idx_cities_admin_division');
        });

        DB::statement('ALTER TABLE cities ADD COLUMN postal_codes varchar[]');

        DB::statement('ALTER TABLE cities ADD COLUMN location geography(Point,4326) NOT NULL');
        DB::statement('ALTER TABLE cities ADD COLUMN boundary geography(MultiPolygon,4326)');
        DB::statement('ALTER TABLE cities ADD COLUMN latitude numeric(10,7) GENERATED ALWAYS AS (ST_Y(location::geometry)) STORED');
        DB::statement('ALTER TABLE cities ADD COLUMN longitude numeric(10,7) GENERATED ALWAYS AS (ST_X(location::geometry)) STORED');

        DB::statement('CREATE INDEX gist_cities_location ON cities USING GIST (location)');
        DB::statement('CREATE INDEX gist_cities_boundary ON cities USING GIST (boundary)');

        DB::statement(<<<'SQL'
            ALTER TABLE cities ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                setweight(to_tsvector('french_unaccent', coalesce(name, '')), 'A')
            ) STORED
        SQL);
        DB::statement('CREATE INDEX gin_cities_search ON cities USING GIN (search_vector)');
        DB::statement('CREATE INDEX gin_cities_name_trgm ON cities USING GIN (name gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
