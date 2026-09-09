<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncActivityCityPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PagePublicationRule;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;

it('returns the city, activity, published content and neighbor cities', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Transport urbain']);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    $neighbor = City::factory()->for($country)->create(['name' => 'Villeurbanne']);
    CityNeighbor::create(['city_id' => $city->id, 'neighbor_city_id' => $neighbor->id, 'distance_m' => 5000, 'rank' => 1]);

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

    $response = $this->getJson("/api/v1/fr/activity-city/{$city->slug}/{$activity->slug}");

    $response->assertOk()
        ->assertJsonPath('data.city.name', 'Lyon')
        ->assertJsonPath('data.activity.label', 'Transport urbain')
        ->assertJsonPath('data.path', "/{$city->slug}/{$activity->slug}")
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.data.body', 'Présentation locale.')
        ->assertJsonPath('data.neighbor_cities.0.slug', $neighbor->slug);
});

it('also includes the generic activity content, shared across every city', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();
    $city = City::factory()->for($country)->create();

    ActivityContent::factory()->create([
        'activity_id' => $activity->id,
        'country_id' => null,
        'section' => 'regulation',
        'title' => 'Réglementation',
        'body' => 'Contenu générique du métier.',
        'status' => ContentStatus::Published,
    ]);

    $response = $this->getJson("/api/v1/fr/activity-city/{$city->slug}/{$activity->slug}");

    $response->assertOk()
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.type', 'regulation')
        ->assertJsonPath('data.blocks.0.data.title', 'Réglementation');
});

it('returns 404 for an unknown city', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();

    $response = $this->getJson("/api/v1/fr/activity-city/does-not-exist/{$activity->slug}");

    $response->assertNotFound();
});

it('returns 404 for an unknown activity', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create();

    $response = $this->getJson("/api/v1/fr/activity-city/{$city->slug}/does-not-exist");

    $response->assertNotFound();
});

it('marks the page non-indexable once synced when the company count is below the publication threshold', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();
    $city = City::factory()->for($country)->create();
    Company::factory()->for($country)->for($city, 'city')->for($activity)->create();

    PagePublicationRule::factory()->create([
        'page_type' => PageType::ActivityCity,
        'country_id' => null,
        'min_companies' => 3,
    ]);

    app(SyncActivityCityPageRouteAction::class)->execute($city, $activity, $country);

    $response = $this->getJson("/api/v1/fr/activity-city/{$city->slug}/{$activity->slug}");

    $response->assertOk()->assertJsonPath('data.is_indexable', false);
});
