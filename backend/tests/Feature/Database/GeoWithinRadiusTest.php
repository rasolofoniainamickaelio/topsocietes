<?php

declare(strict_types=1);

use App\Domain\Geo\Models\City;
use Illuminate\Support\Facades\DB;

/**
 * Paris (48.8566, 2.3522) comme point de référence : une ville à ~1 km
 * (à l'intérieur du rayon de 5 km) et une ville à Lyon, ~390 km (hors
 * rayon), pour vérifier que `ST_DWithin` filtre bien par la distance
 * réelle et non par un simple encadrement de bounding box.
 */
it('filters cities within a radius using ST_DWithin', function (): void {
    $near = City::factory()->create(['name' => 'Near Paris', 'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.36, 48.86), 4326)::geography')]);
    $far = City::factory()->create(['name' => 'Lyon', 'location' => DB::raw('ST_SetSRID(ST_MakePoint(4.8357, 45.7640), 4326)::geography')]);

    $results = City::query()->withinRadius(48.8566, 2.3522, 5000)->get();

    expect($results->pluck('id'))->toContain($near->id)
        ->and($results->pluck('id'))->not->toContain($far->id);
});
