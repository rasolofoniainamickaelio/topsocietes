<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Observers;

use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use App\Domain\Moderation\Notifications\DisputeStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Journalise chaque transition de statut dans `dispute_events` (Phase 22)
 * et notifie le demandeur sur les statuts qui le concernent directement.
 * `Submitted` n'est jamais rejoué ici : cet événement initial est déjà posé
 * par `CreateDisputeReportAction` au moment de la création, avant que ce
 * modèle n'existe pour `updated()`.
 */
class DisputeReportObserver
{
    /** @var array<int, DisputeStatus> */
    private const NOTIFIABLE_STATUSES = [DisputeStatus::Accepted, DisputeStatus::Rejected, DisputeStatus::Applied];

    public function updated(DisputeReport $dispute): void
    {
        if (! $dispute->isDirty('status')) {
            return;
        }

        DisputeEvent::create([
            'dispute_id' => $dispute->id,
            'user_id' => auth()->id(),
            'action' => $dispute->status->value,
        ]);

        if (in_array($dispute->status, self::NOTIFIABLE_STATUSES, true)) {
            Notification::route('mail', $dispute->reporter_email)
                ->notify(new DisputeStatusUpdatedNotification($dispute));
        }
    }
}
