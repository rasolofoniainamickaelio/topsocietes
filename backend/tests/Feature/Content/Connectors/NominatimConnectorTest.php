<?php

declare(strict_types=1);

use App\Domain\Content\Connectors\NominatimConnector;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Support\Facades\Http;

it('collects a place match and extracts osm facts', function (): void {
    Http::fake([
        '*nominatim.openstreetmap.org/search*' => Http::response([
            ['display_name' => 'Lyon, Métropole de Lyon, Rhône, France', 'type' => 'administrative'],
        ], 200),
    ]);

    $country = Country::factory()->create(['name' => 'France']);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);

    $connector = new NominatimConnector;
    $document = $connector->collect($city);

    expect($document)->not->toBeNull()
        ->and($document->normalizedPayload['display_name'])->toBe('Lyon, Métropole de Lyon, Rhône, France');

    $facts = $connector->extractFacts($document, $city);

    expect($facts)->toHaveCount(2)
        ->and(collect($facts)->firstWhere('key', 'osm_type')->value)->toBe('administrative');
});

it('returns null when there is no match', function (): void {
    Http::fake([
        '*nominatim.openstreetmap.org/search*' => Http::response([], 200),
    ]);

    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create();

    $document = (new NominatimConnector)->collect($city);

    expect($document)->toBeNull();
});
