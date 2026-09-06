<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Support\Facades\Notification;

it('lets anyone track a dispute by its tracking number, without authentication', function (): void {
    Notification::fake();

    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $dispute = DisputeReport::factory()->for($company)->create([
        'field' => 'legal_name',
        'status' => DisputeStatus::Submitted,
        'internal_note' => 'Note interne jamais exposée',
        'reporter_email' => 'jean@example.com',
    ]);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}/disputes/{$dispute->id}");

    $response->assertOk()
        ->assertJsonPath('data.tracking_number', $dispute->id)
        ->assertJsonPath('data.status', 'new')
        ->assertJsonMissingPath('data.internal_note')
        ->assertJsonMissingPath('data.reporter_email')
        ->assertJsonMissingPath('data.ip_hash');
});

it('includes the event history in the tracking response', function (): void {
    Notification::fake();

    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $dispute = DisputeReport::factory()->for($company)->create(['status' => DisputeStatus::Submitted]);
    $dispute->events()->create(['action' => 'submitted']);

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}/disputes/{$dispute->id}");

    $response->assertOk()->assertJsonCount(1, 'data.events');
});

it('returns 404 for a dispute id that does not belong to the resolved company', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();
    $otherCompany = Company::factory()->for($country)->create();
    $dispute = DisputeReport::factory()->for($otherCompany)->create();

    $response = $this->getJson("/api/v1/fr/companies/{$company->slug}/disputes/{$dispute->id}");

    $response->assertNotFound();
});
