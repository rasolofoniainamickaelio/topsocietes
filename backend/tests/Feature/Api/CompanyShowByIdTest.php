<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;

it('returns the full company payload by internal id', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    $company = Company::factory()->for($country)->for($city, 'city')->create([
        'legal_name' => 'Keolis Lyon',
    ]);

    $response = $this->getJson("/api/v1/fr/companies/lookup/{$company->id}");

    $response->assertOk()
        ->assertJsonPath('data.legal_name', 'Keolis Lyon')
        ->assertJsonPath('data.slug', $company->slug)
        ->assertJsonPath('data.public_id', $company->public_id)
        ->assertJsonPath('data.path', "/{$city->slug}/{$company->slug}-{$company->public_id}");
});

it('returns 404 for an unknown id', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/companies/lookup/999999');

    $response->assertNotFound();
});

it('does not leak a company belonging to another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'ma', 'is_active' => true]);
    $company = Company::factory()->for($otherCountry)->create();

    $response = $this->getJson("/api/v1/fr/companies/lookup/{$company->id}");

    $response->assertNotFound();
});
