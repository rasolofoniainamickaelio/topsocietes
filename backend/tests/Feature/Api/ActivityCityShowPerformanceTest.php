<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Queries\ActivityCityPageQuery;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Phase 20 : le 2ᵉ assemblage de la page activité×ville doit coûter
 * nettement moins de requêtes SQL que le premier (cache hit).
 */
it('serves a cached activity-city page assembly on the second call', function (): void {
    Cache::flush();

    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();
    $city = City::factory()->for($country)->create();

    CityActivityContent::factory()->create([
        'city_id' => $city->id,
        'activity_id' => $activity->id,
        'section' => 'local_overview',
        'body' => 'Présentation.',
        'status' => ContentStatus::Published,
    ]);

    $query = app(ActivityCityPageQuery::class);

    $countQueries = function () use ($query, $city, $activity, $country): int {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });
        $query->execute($city, $activity, $country);

        return $count;
    };

    $first = $countQueries();
    $second = $countQueries();

    expect($first)->toBeGreaterThan(0)
        ->and($second)->toBe(0);
})->group('performance');
