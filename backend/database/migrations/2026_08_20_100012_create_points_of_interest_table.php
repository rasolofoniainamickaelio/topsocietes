<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Points d'intérêt (OSM, Wikidata, opendata, saisie manuelle). `location`
 * NOT NULL : un POI sans coordonnées n'a pas d'utilité pour le bloc de
 * proximité. `description` est réécrite éditorialement, jamais copiée
 * telle quelle de la source — `attributes` conserve les tags bruts pour
 * traçabilité (chaîne sources → faits, CLAUDE.md §6.1/§6.7).
 * `is_publishable` passe à true après contrôle : import ≠ publication.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_of_interest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->restrictOnDelete();
            $table->string('external_ref');
            $table->string('provider');
            $table->string('name');
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('attributes')->nullable();
            $table->smallInteger('editorial_score')->default(0);
            $table->boolean('is_publishable')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['provider', 'external_ref'], 'uq_points_of_interest_provider_external_ref');
        });

        DB::statement('ALTER TABLE points_of_interest ADD COLUMN location geography(Point,4326) NOT NULL');
        DB::statement('ALTER TABLE points_of_interest ADD COLUMN latitude numeric(10,7) GENERATED ALWAYS AS (ST_Y(location::geometry)) STORED');
        DB::statement('ALTER TABLE points_of_interest ADD COLUMN longitude numeric(10,7) GENERATED ALWAYS AS (ST_X(location::geometry)) STORED');

        DB::statement('CREATE INDEX gist_points_of_interest_location ON points_of_interest USING GIST (location)');
        DB::statement('CREATE INDEX idx_poi_city_category_score ON points_of_interest (city_id, category, editorial_score)');
    }

    public function down(): void
    {
        Schema::dropIfExists('points_of_interest');
    }
};
