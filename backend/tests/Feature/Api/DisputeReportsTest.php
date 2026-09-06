<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use App\Domain\Moderation\Notifications\DisputeSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('creates a dispute report for a company', function (): void {
    Notification::fake();

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

    $response->assertJsonPath('data.tracking_number', $dispute->id);

    expect(DisputeEvent::query()->where('dispute_id', $dispute->id)->where('action', 'new')->exists())->toBeTrue();

    Notification::assertSentOnDemand(
        DisputeSubmittedNotification::class,
        fn (DisputeSubmittedNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'jean.dupont@example.com',
    );
});

it('stores an uploaded evidence file on the local disk', function (): void {
    Notification::fake();
    Storage::fake('local');

    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/disputes", [
        'field' => 'legal_name',
        'proposed_value' => 'Nouveau Nom SARL',
        'reason' => 'Changement de raison sociale.',
        'reporter_name' => 'Jean Dupont',
        'reporter_email' => 'jean.dupont@example.com',
        'evidence' => UploadedFile::fake()->create('justificatif.pdf', 100, 'application/pdf'),
    ]);

    $response->assertCreated();

    $dispute = DisputeReport::query()->where('company_id', $company->id)->firstOrFail();
    expect($dispute->evidence_path)->not->toBeNull();
    Storage::disk('local')->assertExists($dispute->evidence_path);
});

it('rejects an evidence file that is not an accepted type', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $company = Company::factory()->for($country)->create();

    $response = $this->postJson("/api/v1/fr/companies/{$company->slug}/disputes", [
        'field' => 'legal_name',
        'proposed_value' => 'Nouveau Nom SARL',
        'reason' => 'Test',
        'reporter_name' => 'Jean Dupont',
        'reporter_email' => 'jean.dupont@example.com',
        'evidence' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['evidence']);
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
