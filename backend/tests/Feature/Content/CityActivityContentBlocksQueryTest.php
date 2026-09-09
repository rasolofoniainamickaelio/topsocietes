<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Queries\CityActivityContentBlocksQuery;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;

it('returns published content for the exact city and activity pair', function (): void {
    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create();
    $activity = Activity::factory()->create();

    CityActivityContent::factory()->create([
        'city_id' => $city->id,
        'activity_id' => $activity->id,
        'section' => 'local_overview',
        'body' => 'Présentation locale.',
        'status' => ContentStatus::Published,
    ]);
    CityActivityContent::factory()->create([
        'city_id' => $city->id,
        'activity_id' => $activity->id,
        'section' => 'local_history',
        'status' => ContentStatus::Draft,
    ]);

    $blocks = app(CityActivityContentBlocksQuery::class)->execute($city, $activity);

    expect($blocks)->toHaveCount(1)
        ->and($blocks->first()->body)->toBe('Présentation locale.');
});

it('never returns content from a different city or activity', function (): void {
    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create();
    $otherCity = City::factory()->for($country)->create();
    $activity = Activity::factory()->create();
    $otherActivity = Activity::factory()->create();

    CityActivityContent::factory()->create([
        'city_id' => $otherCity->id,
        'activity_id' => $activity->id,
        'status' => ContentStatus::Published,
    ]);
    CityActivityContent::factory()->create([
        'city_id' => $city->id,
        'activity_id' => $otherActivity->id,
        'status' => ContentStatus::Published,
    ]);

    $blocks = app(CityActivityContentBlocksQuery::class)->execute($city, $activity);

    expect($blocks)->toBeEmpty();
});
