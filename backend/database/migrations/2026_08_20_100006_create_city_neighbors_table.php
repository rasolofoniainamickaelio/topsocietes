<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache pré-calculé des communes voisines (contiguïté des `boundary`, à
 * défaut distance des centroïdes). Alimenté par job — jamais de calcul de
 * proximité à l'affichage.
 *
 * `onDelete('cascade')` sur les deux FK : à la différence des FK vers le
 * référentiel géographique lui-même (restrict ailleurs), cette table n'est
 * qu'un cache dérivé, au même titre que `company_nearby_pois`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_neighbors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('neighbor_city_id')->constrained('cities')->cascadeOnDelete();
            $table->integer('distance_m');
            $table->smallInteger('rank');
            $table->timestamps();

            $table->unique(['city_id', 'neighbor_city_id'], 'uq_city_neighbors_city_neighbor');
            $table->index(['city_id', 'rank'], 'idx_city_neighbors_city_rank');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_neighbors');
    }
};
