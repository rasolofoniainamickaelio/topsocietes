<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;

/**
 * Miroir de `BuildCityPathAction` (Phase 13) pour la page activité seule.
 * `Activity.slug` est réellement unique par nomenclature pays (référentiel
 * géré), aucun identifiant de désambiguïsation requis.
 */
class BuildActivityPathAction
{
    private const DEFAULT_PATTERN = '/activite/{activity}';

    public function execute(Activity $activity, Country $country): string
    {
        $urlPatterns = $country->url_patterns ?? [];
        $pattern = is_string($urlPatterns['activity'] ?? null) ? $urlPatterns['activity'] : self::DEFAULT_PATTERN;

        return strtr($pattern, ['{activity}' => $activity->slug]);
    }
}
