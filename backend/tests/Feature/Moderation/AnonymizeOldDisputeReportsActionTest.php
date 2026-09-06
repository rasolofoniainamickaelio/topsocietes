<?php

declare(strict_types=1);

use App\Domain\Moderation\Actions\AnonymizeOldDisputeReportsAction;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('anonymizes a closed dispute past the 24-month retention window and deletes its evidence file', function (): void {
    Storage::fake('local');
    $path = UploadedFile::fake()->create('preuve.pdf', 10)->store('disputes/evidence', 'local');

    $dispute = DisputeReport::factory()->create([
        'status' => DisputeStatus::Applied,
        'resolved_at' => now()->subMonths(25),
        'evidence_path' => $path,
    ]);

    $count = app(AnonymizeOldDisputeReportsAction::class)->execute();

    expect($count)->toBe(1);

    $dispute->refresh();
    expect($dispute->reporter_name)->toBeNull()
        ->and($dispute->reporter_email)->toBeNull()
        ->and($dispute->reporter_phone)->toBeNull()
        ->and($dispute->evidence_path)->toBeNull()
        ->and($dispute->ip_hash)->toBeNull()
        ->and($dispute->anonymized_at)->not->toBeNull();

    Storage::disk('local')->assertMissing($path);
});

it('leaves a recently closed dispute untouched', function (): void {
    $dispute = DisputeReport::factory()->create([
        'status' => DisputeStatus::Applied,
        'resolved_at' => now()->subMonths(6),
    ]);

    app(AnonymizeOldDisputeReportsAction::class)->execute();

    expect($dispute->fresh()->anonymized_at)->toBeNull();
});

it('never touches a dispute that is still being processed, regardless of age', function (): void {
    $dispute = DisputeReport::factory()->create([
        'status' => DisputeStatus::Assigned,
        'created_at' => now()->subMonths(30),
    ]);

    app(AnonymizeOldDisputeReportsAction::class)->execute();

    expect($dispute->fresh()->anonymized_at)->toBeNull();
});

it('does not reprocess an already anonymized dispute', function (): void {
    $dispute = DisputeReport::factory()->create([
        'status' => DisputeStatus::Rejected,
        'resolved_at' => now()->subMonths(30),
        'anonymized_at' => now()->subDay(),
        'reporter_name' => null,
    ]);

    $count = app(AnonymizeOldDisputeReportsAction::class)->execute();

    expect($count)->toBe(0);
});
