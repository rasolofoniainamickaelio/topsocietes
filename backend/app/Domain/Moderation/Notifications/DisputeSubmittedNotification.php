<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Notifications;

use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Accusé de réception (Phase 22) : le numéro de suivi communiqué est
 * directement l'identifiant de la contestation — pas de jeton séparé, cf.
 * le point de suivi public `GET /v1/{country}/companies/{slug}/disputes/{dispute}`.
 * En file (CLAUDE.md §3) : l'envoi ne bloque jamais la réponse HTTP de
 * soumission du signalement.
 */
class DisputeSubmittedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Votre signalement a bien été reçu — Suivi n°'.$this->dispute->id)
            ->greeting('Bonjour '.$this->dispute->reporter_name.',')
            ->line("Votre signalement concernant le champ « {$this->dispute->field} » a bien été enregistré.")
            ->line('Numéro de suivi : '.$this->dispute->id)
            ->line('Nous reviendrons vers vous après examen par notre équipe.');
    }
}
