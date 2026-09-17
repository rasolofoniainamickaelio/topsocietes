<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use Spatie\LaravelData\Data;

/**
 * Liens de la page secteur (Phase 13) : les activités qui le composent,
 * uniquement celles dont la page est déjà synchronisée pour ce pays.
 */
class SectorTerritoryLinksData extends Data
{
    /** @param array<int, InternalLinkData> $activities */
    public function __construct(
        public readonly array $activities,
    ) {}
}
