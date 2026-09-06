<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Notifications;

use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée uniquement sur les transitions qui concernent le demandeur
 * (Accepted/Rejected/Applied) — jamais sur `Assigned`, purement interne au
 * traitement (Phase 22). Déclenchée par `DisputeReportObserver`, jamais
 * directement par une Action, pour ne jamais désynchroniser la
 * journalisation de l'événement et la notification.
 */
class DisputeStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DisputeReport $dispute) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Mise à jour de votre signalement n°'.$this->dispute->id)
            ->greeting('Bonjour '.$this->dispute->reporter_name.',');

        return match ($this->dispute->status) {
            DisputeStatus::Accepted => $message->line('Votre signalement a été accepté et la correction sera appliquée prochainement.'),
            DisputeStatus::Rejected => $message->line("Votre signalement a été examiné et n'a pas été retenu."),
            DisputeStatus::Applied => $message->line('La correction que vous avez signalée a été appliquée sur la fiche entreprise.'),
            default => $message->line('Le statut de votre signalement a été mis à jour.'),
        };
    }
}
