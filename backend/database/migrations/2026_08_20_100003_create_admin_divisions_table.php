<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table unique et auto-référencée pour les divisions administratives
 * (région/wilaya/province au niveau 1, département/préfecture/MRC au
 * niveau 2, etc.) : les 6 pays n'ont pas la même hiérarchie, une table par
 * type de division ne s'appliquerait pas uniformément.
 *
 * `path` matérialise le chemin ("1.12.69") pour les breadcrumbs sans
 * requête récursive. Choix `varchar` plutôt que `ltree` : l'extension
 * ltree n'est pas dans la liste des extensions sanctionnées par le prompt
 * Phase 2, on ne l'introduit pas sans validation explicite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('admin_divisions')->restrictOnDelete();
            $table->smallInteger('level');
            $table->string('code');
            $table->string('name');
            $table->string('slug');
            $table->integer('population')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->string('path')->nullable();
            $table->timestamps();

            $table->unique(['country_id', 'level', 'code'], 'uq_admin_divisions_country_level_code');
            $table->unique(['country_id', 'level', 'slug'], 'uq_admin_divisions_country_level_slug');
            $table->index('parent_id', 'idx_admin_divisions_parent');
        });

        // geography(Point/MultiPolygon,4326) : hors périmètre du Schema Builder.
        // latitude/longitude générées depuis centroid (source de vérité unique,
        // pas de trigger applicatif à maintenir en synchro).
        DB::statement('ALTER TABLE admin_divisions ADD COLUMN boundary geography(MultiPolygon,4326)');
        DB::statement('ALTER TABLE admin_divisions ADD COLUMN centroid geography(Point,4326)');
        DB::statement('ALTER TABLE admin_divisions ADD COLUMN latitude numeric(10,7) GENERATED ALWAYS AS (ST_Y(centroid::geometry)) STORED');
        DB::statement('ALTER TABLE admin_divisions ADD COLUMN longitude numeric(10,7) GENERATED ALWAYS AS (ST_X(centroid::geometry)) STORED');

        DB::statement('CREATE INDEX gist_admin_divisions_boundary ON admin_divisions USING GIST (boundary)');
        DB::statement('CREATE INDEX gist_admin_divisions_centroid ON admin_divisions USING GIST (centroid)');
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_divisions');
    }
};
