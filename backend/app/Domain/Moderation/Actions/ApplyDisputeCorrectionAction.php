<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use InvalidArgumentException;
use LogicException;

/**
 * Applique la correction d'une contestation acceptée (Phase 22) : écrit
 * `proposed_value` sur le champ nommé par `field`. Le `match` ci-dessous
 * est la liste blanche elle-même — volontairement explicite et entièrement
 * typé (jamais une affectation dynamique `[$field => $value]`, que PHPStan
 * ne pourrait pas vérifier contre les colonnes de `Company`) ; elle doit
 * rester synchronisée avec `DisputeCorrectableFields::ALLOWED`, qui pilote
 * l'affichage du bouton Filament. Le passage à `Applied` déclenche
 * `DisputeReportObserver::updated()`, qui journalise l'événement et
 * notifie le demandeur : cette Action ne duplique pas cette logique.
 */
class ApplyDisputeCorrectionAction
{
    public function execute(DisputeReport $dispute): DisputeReport
    {
        if ($dispute->status !== DisputeStatus::Accepted) {
            throw new InvalidArgumentException('Seule une contestation au statut Accepted peut être appliquée.');
        }

        match ($dispute->field) {
            'legal_name' => $dispute->company->update(['legal_name' => $dispute->proposed_value]),
            'trade_name' => $dispute->company->update(['trade_name' => $dispute->proposed_value]),
            'legal_form_label' => $dispute->company->update(['legal_form_label' => $dispute->proposed_value]),
            'headcount_range' => $dispute->company->update(['headcount_range' => $dispute->proposed_value]),
            'about_text' => $dispute->company->update(['about_text' => $dispute->proposed_value]),
            default => throw new LogicException("Le champ « {$dispute->field} » n'est pas dans la liste blanche des corrections autorisées."),
        };

        $dispute->update([
            'status' => DisputeStatus::Applied,
            'resolved_at' => now(),
        ]);

        return $dispute->fresh();
    }
}
