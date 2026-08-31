<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;

it('searches companies by term and returns cursor pagination metadata', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    Company::factory()->for($country)->create(['legal_name' => 'Keolis Lyon']);
    Company::factory()->for($country)->create(['legal_name' => 'Autre Société']);

    $response = $this->getJson('/api/v1/fr/search?term=Keolis');

    $response->assertOk()
        ->assertJsonPath('data.0.legal_name', 'Keolis Lyon')
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data', 'links' => ['next'], 'meta' => ['path', 'per_page']]);
});

it('never returns a company belonging to another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);
    Company::factory()->for($otherCountry)->create(['legal_name' => 'Belge SA']);

    $response = $this->getJson('/api/v1/fr/search?term=Belge');

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('rejects an invalid per_page value', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/search?per_page=999');

    $response->assertUnprocessable();
});
