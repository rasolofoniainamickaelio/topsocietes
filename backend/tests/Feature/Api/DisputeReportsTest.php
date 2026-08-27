<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;

it('creates a dispute report for a company', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/disputes", [
        'field' => 'legal_name',
        'current_value' => 'Ancien Nom SARL',
        'proposed_value' => 'Nouveau Nom SARL',
        'reason' => 'Changement de raison sociale suite à un rachat.',
        'reporter_name' => 'Jean Dupont',
        'reporter_email' => 'jean.dupont@example.com',
        'reporter_phone' => null,
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'submitted');

    $dispute = DisputeReport::query()->where('company_id', $company->id)->firstOrFail();
    expect($dispute->status)->toBe(DisputeStatus::Submitted)
        ->and($dispute->proposed_value)->toBe('Nouveau Nom SARL')
        ->and($dispute->ip_hash)->not->toBeNull();
});

it('rejects a dispute report missing required fields', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/disputes", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['field', 'proposed_value', 'reason', 'reporter_name', 'reporter_email']);
});

it('returns 404 when disputing a company from another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);
    $company = Company::factory()->for($otherCountry)->create();

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/disputes", [
        'field' => 'legal_name',
        'proposed_value' => 'Nouveau Nom',
        'reason' => 'Test',
        'reporter_name' => 'Jean Dupont',
        'reporter_email' => 'jean.dupont@example.com',
    ]);

    $response->assertNotFound();
});
