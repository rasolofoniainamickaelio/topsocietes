<?php

declare(strict_types=1);

use App\Domain\Company\Actions\BuildCompanyPathAction;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;

it('builds the path from the country url pattern', function (): void {
    $country = Country::factory()->create(['url_patterns' => ['company' => '/{city}/{slug}-{public_id}']]);
    $city = City::factory()->for($country)->create(['slug' => 'lyon']);
    $company = Company::factory()->for($country)->for($city, 'city')->create(['slug' => 'plomberie-dupont']);

    $path = app(BuildCompanyPathAction::class)->execute($company);

    expect($path)->toBe("/lyon/plomberie-dupont-{$company->public_id}");
});

it('falls back to a default pattern when the country has none configured', function (): void {
    $country = Country::factory()->create(['url_patterns' => []]);
    $company = Company::factory()->for($country)->create(['slug' => 'acme', 'city_id' => null]);

    $path = app(BuildCompanyPathAction::class)->execute($company);

    expect($path)->toBe("/entreprise/acme-{$company->public_id}");
});

it('accepts overrides to build a past path from an old slug and city', function (): void {
    $country = Country::factory()->create(['url_patterns' => ['company' => '/{city}/{slug}-{public_id}']]);
    $city = City::factory()->for($country)->create(['slug' => 'lyon']);
    $company = Company::factory()->for($country)->for($city, 'city')->create(['slug' => 'nouveau-nom']);

    $oldPath = app(BuildCompanyPathAction::class)->execute($company, slugOverride: 'ancien-nom', citySlugOverride: 'villeurbanne');

    expect($oldPath)->toBe("/villeurbanne/ancien-nom-{$company->public_id}");
});
