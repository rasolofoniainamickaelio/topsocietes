<?php

declare(strict_types=1);

use App\Domain\Company\Actions\GenerateCompanySlugAction;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;

it('generates a slug from the legal name and city', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr']);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);

    $slug = app(GenerateCompanySlugAction::class)->execute($country, 'Acme', $city);

    expect($slug)->toBe('acme-lyon');
});

it('appends a numeric suffix on collision', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr']);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    Company::factory()->for($country)->create(['slug' => 'acme-lyon']);

    $slug = app(GenerateCompanySlugAction::class)->execute($country, 'Acme', $city);

    expect($slug)->toBe('acme-lyon-2');
});

it('scopes uniqueness per country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr']);
    $otherCountry = Country::factory()->create(['subdomain' => 'be']);
    Company::factory()->for($otherCountry)->create(['slug' => 'acme']);

    $slug = app(GenerateCompanySlugAction::class)->execute($country, 'Acme');

    expect($slug)->toBe('acme');
});
