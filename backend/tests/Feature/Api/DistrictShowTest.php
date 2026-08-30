<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\DistrictContent;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;

it('returns a district with its published content blocks', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create();
    $district = District::factory()->for($country)->for($city)->create();
    DistrictContent::factory()->for($district)->create([
        'section' => 'history',
        'status' => ContentStatus::Published,
        'body' => 'Histoire locale',
    ]);
    DistrictContent::factory()->for($district)->create([
        'section' => 'draft-section',
        'status' => ContentStatus::Draft,
    ]);

    $response = $this->getJson("/api/v1/fr/districts/{$district->slug}");

    $response->assertOk()
        ->assertJsonPath('data.name', $district->name)
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.type', 'history');
});

it('returns 404 for an unknown district', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/districts/does-not-exist');

    $response->assertNotFound();
});
