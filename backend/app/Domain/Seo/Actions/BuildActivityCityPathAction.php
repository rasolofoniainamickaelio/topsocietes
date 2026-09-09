<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;

/**
 * Construit le chemin public d'une page activité×ville, à partir du
 * gabarit configuré par pays (`countries.url_patterns.activity_city`,
 * CLAUDE.md §6.6 — jamais une structure codée en dur). Miroir de
 * `App\Domain\Company\Actions\BuildCompanyPathAction` : contrairement au
 * slug d'entreprise, `City.slug` et `Activity.slug` sont chacun réellement
 * uniques (référentiels gérés), donc aucun identifiant de désambiguïsation
 * n'est nécessaire ici.
 */
class BuildActivityCityPathAction
{
    private const DEFAULT_PATTERN = '/{city}/{activity}';

    public function execute(City $city, Activity $activity, Country $country): string
    {
        $urlPatterns = $country->url_patterns ?? [];
        $pattern = is_string($urlPatterns['activity_city'] ?? null) ? $urlPatterns['activity_city'] : self::DEFAULT_PATTERN;

        return strtr($pattern, [
            '{city}' => $city->slug,
            '{activity}' => $activity->slug,
        ]);
    }
}
