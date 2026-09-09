<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildActivityCityPathAction;
use App\Domain\Seo\Data\CityTerritoryLinksData;
use App\Domain\Seo\Data\InternalLinkData;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Activity;

/**
 * Maillage des pages territoriales elles-mêmes (Phase 13) — distinct
 * d'`InternalLinkingService` (Phase 15), qui ne sert que les fiches
 * entreprise. Réduit à `City` pour l'instant : `forAdminDivision()`/
 * `forActivity()`/etc. suivront le même moule quand ces pages existeront.
 */
class TerritoryLinkingService
{
    private const MAX_ACTIVITIES = 20;

    public function __construct(private readonly BuildActivityCityPathAction $buildActivityCityPath) {}

    public function forCity(City $city, Country $country): CityTerritoryLinksData
    {
        return new CityTerritoryLinksData(
            country: new InternalLinkData(
                type: PageType::Editorial,
                label: $country->name,
                params: ['path' => '/'],
                path: '/',
            ),
            activities: $this->activitiesInCity($city, $country),
        );
    }

    /**
     * Seules les activités dont la page activité×ville (Phase 12) est déjà
     * synchronisée sont liées — jamais un lien vers une route qui n'existe
     * pas encore (CLAUDE.md §6.5).
     *
     * @return array<int, InternalLinkData>
     */
    private function activitiesInCity(City $city, Country $country): array
    {
        $activityIds = Company::query()
            ->where('city_id', $city->id)
            ->whereNotNull('activity_id')
            ->distinct()
            ->pluck('activity_id');

        return Activity::query()
            ->whereIn('id', $activityIds)
            ->orderBy('public_label')
            ->limit(self::MAX_ACTIVITIES)
            ->get()
            ->map(function (Activity $activity) use ($city, $country) {
                $path = $this->buildActivityCityPath->execute($city, $activity, $country);

                $exists = PageRoute::query()
                    ->where('country_id', $country->id)
                    ->where('path', $path)
                    ->exists();

                if (! $exists) {
                    return null;
                }

                return new InternalLinkData(
                    type: PageType::ActivityCity,
                    label: "{$activity->public_label} à {$city->name}",
                    params: ['citySlug' => $city->slug, 'activitySlug' => $activity->slug],
                    path: $path,
                );
            })
            ->filter()
            ->values()
            ->all();
    }
}
