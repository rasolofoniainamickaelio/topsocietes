<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use Spatie\LaravelData\Data;

/**
 * Le maillage interne complet d'une fiche entreprise (Phase 15) — un groupe
 * absent (tableau vide / `null`) signifie simplement qu'il n'y a rien de
 * pertinent à proposer, jamais une erreur (CLAUDE.md §6.5).
 */
class CompanyLinksData extends Data
{
    /**
     * @param  array<int, InternalLinkData>  $sameTradeInCity
     * @param  array<int, InternalLinkData>  $nearby
     * @param  array<int, InternalLinkData>  $activityInNeighborCities
     * @param  array<int, InternalLinkData>  $relatedActivities
     */
    public function __construct(
        public readonly array $sameTradeInCity,
        public readonly array $nearby,
        public readonly ?InternalLinkData $activityInCity,
        public readonly array $activityInNeighborCities,
        public readonly ?InternalLinkData $department,
        public readonly ?InternalLinkData $region,
        public readonly ?InternalLinkData $country,
        public readonly array $relatedActivities,
        public readonly ?InternalLinkData $companyCreation,
    ) {}
}
