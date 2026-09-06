<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Models\AiBudget;
use App\Domain\Geo\Models\Country;

/**
 * N'enregistre une dépense que pour un appel fournisseur réellement effectué
 * (jamais pour une génération rejetée avant l'appel), et seulement si un
 * plafond a été explicitement configuré pour ce (pays, mois) — créer la
 * ligne à la volée avec un plafond arbitraire ferait passer
 * `CheckAiBudgetAction` en blocage dès le premier coût enregistré, ce qui
 * reviendrait à imposer un quota que personne n'a demandé.
 */
class RecordAiSpendAction
{
    public function execute(Country $country, int $costCents): void
    {
        if ($costCents <= 0) {
            return;
        }

        AiBudget::query()
            ->where('country_id', $country->id)
            ->where('period', now()->format('Y-m'))
            ->increment('spent_cents', $costCents);
    }
}
