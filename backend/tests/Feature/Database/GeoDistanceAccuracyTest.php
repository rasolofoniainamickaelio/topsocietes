<?php

declare(strict_types=1);

use App\Domain\Geo\Models\City;
use Illuminate\Support\Facades\DB;

/**
 * Un degré de latitude vaut ~111 195 m sur l'ellipsoïde WGS84 (référence
 * géodésique standard) : sert de mesure de contrôle indépendante pour
 * vérifier que `ST_Distance` (geography) renvoie une distance réaliste,
 * et que `orderByDistanceFrom` trie effectivement du plus proche au plus
 * loin.
 */
it('computes an accurate geodesic distance with ST_Distance', function (): void {
    $reference = ['lat' => 48.0, 'lng' => 2.0];
    City::factory()->create(['name' => 'One degree north', 'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.0, 49.0), 4326)::geography')]);

    $result = City::query()->orderByDistanceFrom($reference['lat'], $reference['lng'])->firstOrFail();

    expect((float) $result->distance_m)->toBeGreaterThan(110_000.0)->toBeLessThan(112_000.0);
});

it('orders results from nearest to farthest', function (): void {
    $reference = ['lat' => 48.8566, 'lng' => 2.3522];

    $far = City::factory()->create(['name' => 'Marseille', 'location' => DB::raw('ST_SetSRID(ST_MakePoint(5.3698, 43.2965), 4326)::geography')]);
    $near = City::factory()->create(['name' => 'Near Paris', 'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.36, 48.86), 4326)::geography')]);

    $ordered = City::query()->orderByDistanceFrom($reference['lat'], $reference['lng'])->pluck('id');

    expect($ordered->first())->toBe($near->id)
        ->and($ordered->last())->toBe($far->id);
});
