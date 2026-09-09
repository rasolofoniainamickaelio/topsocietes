<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;
use App\Domain\Seo\Actions\SyncActivityCityPageRouteAction;
use App\Domain\Taxonomy\Models\Activity;
use Database\Factories\CityContentFactory;

it('lists cities scoped to the resolved country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);

    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    City::factory()->for($otherCountry)->create(['name' => 'Bruxelles']);

    $response = $this->getJson('/api/v1/fr/cities');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('slug'))->toContain($city->slug);
    expect(collect($response->json('data'))->pluck('name'))->not->toContain('Bruxelles');
});

it('filters cities by search term', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    City::factory()->for($country)->create(['name' => 'Marseille']);
    City::factory()->for($country)->create(['name' => 'Toulouse']);

    $response = $this->getJson('/api/v1/fr/cities?search=Marseille');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Marseille']);
});

it('shows a city with its districts, neighbors and published blocks', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create(['name' => 'Nantes']);
    $district = District::factory()->for($city)->create();
    $neighbor = City::factory()->for($country)->create(['name' => 'Rennes']);

    CityNeighbor::create([
        'city_id' => $city->id,
        'neighbor_city_id' => $neighbor->id,
        'distance_m' => 100_000,
        'rank' => 1,
    ]);

    $published = CityContentFactory::new()->for($city)->create([
        'status' => ContentStatus::Published,
        'title' => 'Histoire de Nantes',
    ]);
    CityContentFactory::new()->for($city)->create(['status' => ContentStatus::Draft, 'section' => 'nature']);

    $response = $this->getJson("/api/v1/fr/cities/{$city->slug}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'Nantes')
        ->assertJsonPath('data.districts.0.slug', $district->slug)
        ->assertJsonPath('data.neighbors.0.slug', $neighbor->slug)
        ->assertJsonPath('data.neighbors.0.distance_m', 100_000)
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.data.title', 'Histoire de Nantes');
});

it('exposes its path, indexable status and territory links', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    $activity = Activity::factory()->create(['public_label' => 'Transport urbain']);
    Company::factory()->for($country)->for($city, 'city')->for($activity)->create();

    // La page activité×ville (Phase 12) doit déjà être synchronisée pour
    // que le lien descendant apparaisse (jamais un lien vers une route
    // qui n'existe pas encore).
    app(SyncActivityCityPageRouteAction::class)->execute($city, $activity, $country);

    $response = $this->getJson("/api/v1/fr/cities/{$city->slug}");

    $response->assertOk()
        ->assertJsonPath('data.path', "/{$city->slug}")
        ->assertJsonPath('data.is_indexable', true)
        ->assertJsonPath('data.links.country.label', $country->name)
        ->assertJsonPath('data.links.activities.0.label', 'Transport urbain à Lyon');
});

it('omits an activity link when its activity-city route is not yet synced', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create();
    $activity = Activity::factory()->create();
    Company::factory()->for($country)->for($city, 'city')->for($activity)->create();
    // Pas de synchronisation ici : la route activité×ville n'existe pas.

    $response = $this->getJson("/api/v1/fr/cities/{$city->slug}");

    $response->assertOk()->assertJsonCount(0, 'data.links.activities');
});

it('exposes its department and region, for the breadcrumb', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $region = AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Auvergne-Rhône-Alpes']);
    $department = AdminDivision::factory()->for($country)->create([
        'level' => 2,
        'parent_id' => $region->id,
        'name' => 'Rhône',
    ]);
    $city = City::factory()->for($country)->create(['admin_division_id' => $department->id]);

    $response = $this->getJson("/api/v1/fr/cities/{$city->slug}");

    $response->assertOk()
        ->assertJsonPath('data.admin_division.name', 'Rhône')
        ->assertJsonPath('data.admin_division.region', 'Auvergne-Rhône-Alpes');
});

it('omits admin_division when the city has none resolved', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create(['admin_division_id' => null]);

    $response = $this->getJson("/api/v1/fr/cities/{$city->slug}");

    $response->assertOk()->assertJsonPath('data.admin_division', null);
});

it('returns 404 for an unknown city slug', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/cities/does-not-exist');

    $response->assertNotFound();
});

it('never returns a city belonging to another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);
    $city = City::factory()->for($otherCountry)->create();

    $response = $this->getJson("/api/v1/fr/cities/{$city->slug}");

    $response->assertNotFound();
});
