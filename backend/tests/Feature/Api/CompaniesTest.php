<?php

declare(strict_types=1);

use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyContact;
use App\Domain\Company\Models\CompanyNearbyPoi;
use App\Domain\Company\Models\Establishment;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;

it('lists companies scoped to the resolved country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);

    $company = Company::factory()->for($country)->create(['legal_name' => 'Acme SARL']);
    Company::factory()->for($otherCountry)->create(['legal_name' => 'Other Corp']);

    $response = $this->getJson('/api/v1/fr/companies');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('slug')->all())->toBe([$company->slug]);
});

it('filters companies by city slug', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $paris = City::factory()->for($country)->create(['name' => 'Paris']);
    $lyon = City::factory()->for($country)->create(['name' => 'Lyon']);

    $inParis = Company::factory()->for($country)->for($paris, 'city')->create(['legal_name' => 'Paris Corp']);
    Company::factory()->for($country)->for($lyon, 'city')->create(['legal_name' => 'Lyon Corp']);

    $response = $this->getJson("/api/v1/fr/companies?city={$paris->slug}");

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('slug')->all())->toBe([$inParis->slug]);
});

it('never returns a company belonging to another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);
    $company = Company::factory()->for($otherCountry)->create();

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertNotFound();
});

it('shows a company with its activity, city, main establishment and nearby POIs', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create(['name' => 'Bordeaux']);
    $activity = Activity::factory()->create(['public_label' => 'Boulangerie']);

    $company = Company::factory()->for($country)->for($city, 'city')->for($activity)->create([
        'legal_name' => 'Boulangerie Dupont',
        'about_text' => 'Une boulangerie artisanale au coeur de Bordeaux.',
    ]);
    $establishment = Establishment::factory()->for($company)->create(['is_headquarters' => true]);
    $company->update(['main_establishment_id' => $establishment->id]);

    CompanyNearbyPoi::factory()->for($company)->create(['distance_m' => 250, 'rank' => 1]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk()
        ->assertJsonPath('data.legal_name', 'Boulangerie Dupont')
        ->assertJsonPath('data.activity.label', 'Boulangerie')
        ->assertJsonPath('data.city.name', 'Bordeaux')
        ->assertJsonPath('data.main_establishment.is_headquarters', true)
        ->assertJsonCount(1, 'data.nearby_pois')
        ->assertJsonPath('data.nearby_pois.0.distance_m', 250);
});

it('never includes the contacts key when no contact is unlocked', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    CompanyContact::factory()->for($company)->create(['visibility' => ContactVisibility::Hidden]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk();
    expect($response->json('data'))->not->toHaveKey('contacts');
});

it('includes only unlocked contacts', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    CompanyContact::factory()->for($company)->create(['visibility' => ContactVisibility::Hidden, 'value' => 'hidden-phone']);
    CompanyContact::factory()->for($company)->create(['visibility' => ContactVisibility::Visible, 'value' => 'visible-phone']);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk();
    expect(collect($response->json('data.contacts'))->pluck('value')->all())->toBe(['visible-phone']);
});
