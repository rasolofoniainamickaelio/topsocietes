<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;

it('returns ranked suggestions for a partial term', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    Company::factory()->for($country)->create(['legal_name' => 'Keolis Lyon']);
    Company::factory()->for($country)->create(['legal_name' => 'Autre Société']);

    $response = $this->getJson('/api/v1/fr/search/autocomplete?term=Keolis');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.legal_name', 'Keolis Lyon');
});

it('returns an empty list for a blank term', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/search/autocomplete');

    $response->assertOk()->assertJsonCount(0, 'data');
});
