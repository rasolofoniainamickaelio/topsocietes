<?php

declare(strict_types=1);

use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use App\Domain\Moderation\Notifications\DisputeStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;

it('logs an event and notifies the reporter when the status changes to Accepted', function (): void {
    Notification::fake();

    $dispute = DisputeReport::factory()->create(['status' => DisputeStatus::Submitted, 'reporter_email' => 'jean@example.com']);

    $dispute->update(['status' => DisputeStatus::Accepted]);

    expect(DisputeEvent::query()->where('dispute_id', $dispute->id)->where('action', 'accepted')->exists())->toBeTrue();

    Notification::assertSentOnDemand(
        DisputeStatusUpdatedNotification::class,
        fn (DisputeStatusUpdatedNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'jean@example.com',
    );
});

it('does not notify or log anything for an Assigned transition', function (): void {
    Notification::fake();

    $dispute = DisputeReport::factory()->create(['status' => DisputeStatus::Submitted]);

    $dispute->update(['status' => DisputeStatus::Assigned]);

    expect(DisputeEvent::query()->where('dispute_id', $dispute->id)->where('action', 'assigned')->exists())->toBeTrue();
    Notification::assertNothingSent();
});

it('does nothing when saving without an actual status change', function (): void {
    Notification::fake();

    $dispute = DisputeReport::factory()->create(['status' => DisputeStatus::Submitted]);
    $dispute->update(['internal_note' => 'Note interne']);

    expect(DisputeEvent::query()->where('dispute_id', $dispute->id)->count())->toBe(0);
    Notification::assertNothingSent();
});
