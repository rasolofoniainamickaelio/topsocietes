<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use Spatie\LaravelData\Data;

/**
 * Liens de la page pays (Phase 13) : les régions (divisions de niveau 1)
 * et les activités principales, uniquement celles déjà synchronisées.
 */
class CountryTerritoryLinksData extends Data
{
    /**
     * @param  array<int, InternalLinkData>  $regions
     * @param  array<int, InternalLinkData>  $activities
     */
    public function __construct(
        public readonly array $regions,
        public readonly array $activities,
    ) {}
}
