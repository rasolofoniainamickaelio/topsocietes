<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;

/**
 * Construit le chemin public d'une page ville, à partir du gabarit
 * configuré par pays (`countries.url_patterns.city`, CLAUDE.md §6.6 —
 * jamais une structure codée en dur). Miroir de `BuildCompanyPathAction`/
 * `BuildActivityCityPathAction` : `City.slug` est réellement unique par
 * pays (référentiel géré), aucun identifiant de désambiguïsation requis.
 */
class BuildCityPathAction
{
    private const DEFAULT_PATTERN = '/{city}';

    public function execute(City $city, Country $country): string
    {
        $urlPatterns = $country->url_patterns ?? [];
        $pattern = is_string($urlPatterns['city'] ?? null) ? $urlPatterns['city'] : self::DEFAULT_PATTERN;

        return strtr($pattern, ['{city}' => $city->slug]);
    }
}
