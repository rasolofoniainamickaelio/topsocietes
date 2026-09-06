<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Models\AiBudget;
use App\Domain\Geo\Models\Country;

/**
 * Aucune ligne `ai_budgets` pour le (pays, mois) courant = aucun plafond
 * configuré = génération autorisée (Phase 10, "gestion des coûts... quotas"
 * est une limite optionnelle, pas une exigence de configuration préalable).
 */
class CheckAiBudgetAction
{
    public function execute(Country $country): bool
    {
        $budget = AiBudget::query()
            ->where('country_id', $country->id)
            ->where('period', now()->format('Y-m'))
            ->first();

        return $budget === null || $budget->spent_cents < $budget->max_cost_cents;
    }
}
