<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;
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
