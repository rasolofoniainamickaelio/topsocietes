<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache pré-calculé du bloc de proximité : N meilleurs POI par entreprise
 * publiée (typiquement 20–30, rayon paramétrable jusqu'à 20 km), alimenté
 * par job. Jamais de `ST_DWithin` à l'affichage à cette volumétrie.
 * `distance_m` vient toujours de `ST_Distance` sur `geography` — jamais
 * calculée applicativement ni par un LLM.
 *
 * `generated_at` permet le rafraîchissement incrémental (ne retraiter que
 * les entreprises dont le cache est périmé).
 *
 * `onDelete('cascade')` sur les deux FK (§5.6, liste explicite) : pure
 * table de cache dérivée, pas de relation métier à protéger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_nearby_pois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poi_id')->constrained('points_of_interest')->cascadeOnDelete();
            $table->integer('distance_m');
            $table->smallInteger('rank');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'poi_id'], 'uq_company_nearby_pois_company_poi');
            $table->index(['company_id', 'rank'], 'idx_company_nearby_rank');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_nearby_pois');
    }
};
