<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Établissements (SIRET) d'une entreprise. `country_id` est dénormalisé
 * depuis `company_id` (décision structurante multi-pays : country_id en
 * tête de tous les index composites métier), ce qui permet la même clé de
 * rapprochement à l'import que pour `companies` : (country_id, national_id).
 *
 * `onDelete('cascade')` sur `company_id` (§5.6, liste explicite) : un
 * établissement ne survit jamais à la suppression de son entreprise.
 *
 * La contrainte FK `companies.main_establishment_id → establishments.id`
 * est ajoutée ici, à la fin de cette migration, une fois la table cible
 * créée (référence en avant depuis la migration précédente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('national_id');
            $table->boolean('is_headquarters')->default(false);
            $table->string('street_number')->nullable();
            $table->string('street_name')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->restrictOnDelete();
            $table->string('geocoding_status');
            $table->string('status');
            $table->foreignId('activity_id')->nullable()->constrained('activities')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['country_id', 'national_id'], 'uq_establishments_country_national');
            $table->index('city_id', 'idx_establishments_city');
        });

        DB::statement('ALTER TABLE establishments ADD COLUMN location geography(Point,4326)');
        DB::statement('ALTER TABLE establishments ADD COLUMN latitude numeric(10,7) GENERATED ALWAYS AS (ST_Y(location::geometry)) STORED');
        DB::statement('ALTER TABLE establishments ADD COLUMN longitude numeric(10,7) GENERATED ALWAYS AS (ST_X(location::geometry)) STORED');
        DB::statement('CREATE INDEX gist_establishments_location ON establishments USING GIST (location)');

        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('main_establishment_id', 'fk_companies_main_establishment')
                ->references('id')->on('establishments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign('fk_companies_main_establishment');
        });

        Schema::dropIfExists('establishments');
    }
};
