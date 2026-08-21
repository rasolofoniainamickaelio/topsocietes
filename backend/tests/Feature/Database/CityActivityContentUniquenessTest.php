<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\QueryException;

it('rejects a duplicate (city, activity, locale, section) row', function (): void {
    $city = City::factory()->create();
    $activity = Activity::factory()->create();

    CityActivityContent::factory()->create([
        'city_id' => $city->id,
        'activity_id' => $activity->id,
        'locale' => 'fr',
        'section' => ContentSection::LocalOverview->value,
    ]);

    expect(fn () => CityActivityContent::factory()->create([
        'city_id' => $city->id,
        'activity_id' => $activity->id,
        'locale' => 'fr',
        'section' => ContentSection::LocalOverview->value,
    ]))->toThrow(QueryException::class);
});

it('rejects a duplicate (city, sector, locale, section) row even though sector rows have a null activity_id', function (): void {
    $city = City::factory()->create();
    $sector = Sector::factory()->create();

    CityActivityContent::factory()->forSector()->create([
        'city_id' => $city->id,
        'sector_id' => $sector->id,
        'locale' => 'fr',
        'section' => ContentSection::LocalOverview->value,
    ]);

    expect(fn () => CityActivityContent::factory()->forSector()->create([
        'city_id' => $city->id,
        'sector_id' => $sector->id,
        'locale' => 'fr',
        'section' => ContentSection::LocalOverview->value,
    ]))->toThrow(QueryException::class);
});

it('allows the same activity in two different cities', function (): void {
    $activity = Activity::factory()->create();
    $cityA = City::factory()->create();
    $cityB = City::factory()->create();

    CityActivityContent::factory()->create(['city_id' => $cityA->id, 'activity_id' => $activity->id, 'locale' => 'fr', 'section' => ContentSection::LocalOverview->value]);
    $second = CityActivityContent::factory()->create(['city_id' => $cityB->id, 'activity_id' => $activity->id, 'locale' => 'fr', 'section' => ContentSection::LocalOverview->value]);

    expect($second->exists)->toBeTrue();
});
