<?php

declare(strict_types=1);

use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyContact;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;

it('returns the full company payload including identity, legal, geo and sector fields', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $sector = Sector::factory()->create(['name' => 'Transport']);
    $activity = Activity::factory()->create(['public_label' => 'Transport urbain']);
    $activity->sectors()->attach($sector);

    $company = Company::factory()->for($country)->create([
        'national_id' => '123456789',
        'legal_form_code' => '5710',
        'legal_form_label' => 'SAS',
        'activity_id' => $activity->id,
    ]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk()
        ->assertJsonPath('data.national_id', '123456789')
        ->assertJsonPath('data.legal_form_label', 'SAS')
        ->assertJsonPath('data.activity.label', 'Transport urbain')
        ->assertJsonPath('data.activity.sectors.0.name', 'Transport')
        ->assertJsonMissingPath('data.contacts');
});

it('omits the contacts key entirely when no contact is visible', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    CompanyContact::factory()->for($company)->create(['visibility' => ContactVisibility::Hidden]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk()->assertJsonMissingPath('data.contacts');
});

it('does not error when activity and city are not yet resolved', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create(['activity_id' => null, 'city_id' => null]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk()
        ->assertJsonPath('data.activity', null)
        ->assertJsonPath('data.city', null);
});

it('includes visible contacts', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    CompanyContact::factory()->for($company)->create([
        'type' => 'phone',
        'value' => '+33100000000',
        'visibility' => ContactVisibility::Visible,
    ]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk()->assertJsonPath('data.contacts.0.value', '+33100000000');
});

it('includes the published city content as a territorial block', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $city = City::factory()->for($country)->create();
    $company = Company::factory()->for($country)->create(['city_id' => $city->id]);
    CityContent::factory()->for($city)->create([
        'section' => 'history',
        'title' => 'Histoire de la ville',
        'body' => 'Fondée il y a longtemps.',
        'status' => ContentStatus::Published,
    ]);
    CityContent::factory()->for($city)->create([
        'section' => 'nature',
        'status' => ContentStatus::Draft,
    ]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}");

    $response->assertOk()
        ->assertJsonPath('data.blocks.0.type', 'history')
        ->assertJsonPath('data.blocks.0.data.body', 'Fondée il y a longtemps.')
        ->assertJsonCount(1, 'data.blocks');
});

it('returns 404 for an unknown slug', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/companies/does-not-exist');

    $response->assertNotFound();
});
