<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Geo\Models\Country;
use App\Models\User;

it('lets an authenticated user submit a claim on a company', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/claims", [
        'verification_method' => 'email_domain',
        'evidence_path' => null,
    ]);

    $response->assertCreated()->assertJsonPath('data.status', CompanyClaimStatus::Pending->value);

    $claim = CompanyClaim::query()->where('company_id', $company->id)->firstOrFail();
    expect($claim->user_id)->toBe($user->id)
        ->and($claim->status)->toBe(CompanyClaimStatus::Pending);
});

it('rejects a claim submission from a guest', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/claims", [
        'verification_method' => 'email_domain',
    ]);

    $response->assertUnauthorized();
});

it('rejects a claim with an invalid verification method', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/claims", [
        'verification_method' => 'not_a_real_method',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['verification_method']);
});

it('returns 404 when claiming a company from another country', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $otherCountry = Country::factory()->create(['subdomain' => 'be', 'is_active' => true]);
    $company = Company::factory()->for($otherCountry)->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/v1/fr/companies/{$company->slug}/claims", [
        'verification_method' => 'email_domain',
    ]);

    $response->assertNotFound();
});
