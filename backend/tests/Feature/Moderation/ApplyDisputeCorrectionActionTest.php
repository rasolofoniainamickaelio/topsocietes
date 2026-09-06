<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Moderation\Actions\ApplyDisputeCorrectionAction;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Support\Facades\Notification;

it('writes the proposed value onto the company and marks the dispute Applied', function (): void {
    Notification::fake();

    $company = Company::factory()->create(['legal_name' => 'Ancien Nom SARL']);
    $dispute = DisputeReport::factory()->for($company)->create([
        'field' => 'legal_name',
        'proposed_value' => 'Nouveau Nom SARL',
        'status' => DisputeStatus::Accepted,
    ]);

    $result = app(ApplyDisputeCorrectionAction::class)->execute($dispute);

    expect($result->status)->toBe(DisputeStatus::Applied)
        ->and($result->resolved_at)->not->toBeNull()
        ->and($company->fresh()->legal_name)->toBe('Nouveau Nom SARL');
});

it('logs an applied event through the model observer', function (): void {
    Notification::fake();

    $company = Company::factory()->create();
    $dispute = DisputeReport::factory()->for($company)->create([
        'field' => 'legal_name',
        'status' => DisputeStatus::Accepted,
    ]);

    app(ApplyDisputeCorrectionAction::class)->execute($dispute);

    expect(DisputeEvent::query()->where('dispute_id', $dispute->id)->where('action', 'applied')->exists())->toBeTrue();
});

it('refuses to apply a dispute that is not Accepted', function (): void {
    $dispute = DisputeReport::factory()->create(['status' => DisputeStatus::Submitted]);

    app(ApplyDisputeCorrectionAction::class)->execute($dispute);
})->throws(InvalidArgumentException::class);

it('refuses to apply a correction targeting a field outside the whitelist', function (): void {
    Notification::fake();

    $dispute = DisputeReport::factory()->create([
        'field' => 'geocoding_status',
        'status' => DisputeStatus::Accepted,
    ]);

    app(ApplyDisputeCorrectionAction::class)->execute($dispute);
})->throws(LogicException::class);
