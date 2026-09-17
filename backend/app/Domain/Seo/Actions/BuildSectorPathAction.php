<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Sector;

/**
 * Miroir de `BuildActivityPathAction` (Phase 13) pour la page secteur.
 * `Sector` est une entité transversale (pas rattachée à un pays), mais sa
 * page est bien servie par pays, comme la page activité×ville — le
 * gabarit reste piloté par `country.url_patterns.sector`.
 */
class BuildSectorPathAction
{
    private const DEFAULT_PATTERN = '/secteur/{sector}';

    public function execute(Sector $sector, Country $country): string
    {
        $urlPatterns = $country->url_patterns ?? [];
        $pattern = is_string($urlPatterns['sector'] ?? null) ? $urlPatterns['sector'] : self::DEFAULT_PATTERN;

        return strtr($pattern, ['{sector}' => $sector->slug]);
    }
}
