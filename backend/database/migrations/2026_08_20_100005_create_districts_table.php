<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quartiers. `country_id` est dénormalisé depuis `city_id` pour garder
 * `country_id` en tête des index composites sur cette table aussi (décision
 * structurante multi-pays), au même titre que `companies.city_id`.
 *
 * `boundary` est indispensable pour rattacher une entreprise à son quartier
 * par `ST_Contains` (Phase 4 — résolution géo, hors périmètre de cette
 * session) : la colonne reste nullable au niveau du schéma (les quartiers
 * n'ont pas tous une source de polygone dès leur création), mais aucun
 * rattachement automatique ne peut avoir lieu tant qu'elle est vide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->integer('population')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->integer('companies_count')->default(0);
            $table->timestamp('counts_updated_at')->nullable();
            $table->boolean('has_local_content')->default(false);
            $table->timestamps();

            $table->unique(['city_id', 'slug'], 'uq_districts_city_slug');
            $table->index('country_id', 'idx_districts_country');
        });

        DB::statement('ALTER TABLE districts ADD COLUMN location geography(Point,4326)');
        DB::statement('ALTER TABLE districts ADD COLUMN boundary geography(MultiPolygon,4326)');
        DB::statement('ALTER TABLE districts ADD COLUMN latitude numeric(10,7) GENERATED ALWAYS AS (ST_Y(location::geometry)) STORED');
        DB::statement('ALTER TABLE districts ADD COLUMN longitude numeric(10,7) GENERATED ALWAYS AS (ST_X(location::geometry)) STORED');

        DB::statement('CREATE INDEX gist_districts_location ON districts USING GIST (location)');
        DB::statement('CREATE INDEX gist_districts_boundary ON districts USING GIST (boundary)');

        DB::statement(<<<'SQL'
            ALTER TABLE districts ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                setweight(to_tsvector('french_unaccent', coalesce(name, '')), 'A')
            ) STORED
        SQL);
        DB::statement('CREATE INDEX gin_districts_search ON districts USING GIN (search_vector)');
        DB::statement('CREATE INDEX gin_districts_name_trgm ON districts USING GIN (name gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
