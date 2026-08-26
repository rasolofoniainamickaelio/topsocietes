<?php

declare(strict_types=1);

use App\Domain\Geo\Actions\ComputeCityNeighborsAction;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use App\Domain\Geo\Models\Country;
use Illuminate\Support\Facades\DB;

it('includes a boundary-contiguous city as a neighbor', function (): void {
    $country = Country::factory()->create();
    $center = City::factory()->for($country)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
        'boundary' => DB::raw("ST_Multi(ST_SetSRID(ST_GeomFromText('POLYGON((2.30 48.85, 2.40 48.85, 2.40 48.90, 2.30 48.90, 2.30 48.85))'), 4326))::geography"),
    ]);
    $touching = City::factory()->for($country)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.45, 48.87), 4326)::geography'),
        'boundary' => DB::raw("ST_Multi(ST_SetSRID(ST_GeomFromText('POLYGON((2.40 48.85, 2.50 48.85, 2.50 48.90, 2.40 48.90, 2.40 48.85))'), 4326))::geography"),
    ]);

    app(ComputeCityNeighborsAction::class)->execute($center);

    expect(CityNeighbor::query()->where('city_id', $center->id)->pluck('neighbor_city_id'))
        ->toContain($touching->id);
});

it('fills up to 8 neighbors by distance when fewer than 8 are contiguous', function (): void {
    $country = Country::factory()->create();
    $center = City::factory()->for($country)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);

    $cities = [];
    for ($i = 1; $i <= 10; $i++) {
        $lng = 2.35 + ($i * 0.1);
        $cities[$i] = City::factory()->for($country)->create([
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, 48.87), 4326)::geography"),
        ]);
    }

    app(ComputeCityNeighborsAction::class)->execute($center);

    $neighborIds = CityNeighbor::query()->where('city_id', $center->id)->pluck('neighbor_city_id');

    expect($neighborIds)->toHaveCount(8)
        ->and($neighborIds)->toContain($cities[1]->id)
        ->and($neighborIds)->not->toContain($cities[9]->id)
        ->and($neighborIds)->not->toContain($cities[10]->id);
});

it('excludes a city from a different country even if very close', function (): void {
    $country = Country::factory()->create();
    $otherCountry = Country::factory()->create();
    $center = City::factory()->for($country)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);
    City::factory()->for($otherCountry)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.36, 48.87), 4326)::geography'),
    ]);

    app(ComputeCityNeighborsAction::class)->execute($center);

    expect(CityNeighbor::query()->where('city_id', $center->id)->count())->toBe(0);
});
