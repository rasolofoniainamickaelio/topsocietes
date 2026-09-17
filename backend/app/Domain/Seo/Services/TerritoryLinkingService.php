<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildActivityCityPathAction;
use App\Domain\Seo\Data\ActivityTerritoryLinksData;
use App\Domain\Seo\Data\AdminDivisionTerritoryLinksData;
use App\Domain\Seo\Data\CityTerritoryLinksData;
use App\Domain\Seo\Data\CountryTerritoryLinksData;
use App\Domain\Seo\Data\InternalLinkData;
use App\Domain\Seo\Data\SectorTerritoryLinksData;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\Model;

/**
 * Maillage des pages territoriales elles-mêmes (Phase 13) — distinct
 * d'`InternalLinkingService` (Phase 15), qui ne sert que les fiches
 * entreprise.
 */
class TerritoryLinkingService
{
    private const MAX_ACTIVITIES = 20;

    private const MAX_CHILDREN = 50;

    public function __construct(private readonly BuildActivityCityPathAction $buildActivityCityPath) {}

    public function forCity(City $city, Country $country): CityTerritoryLinksData
    {
        $department = $city->adminDivision;
        $region = $department?->parent;

        return new CityTerritoryLinksData(
            country: new InternalLinkData(
                type: PageType::Editorial,
                label: $country->name,
                params: ['path' => '/'],
                path: '/',
            ),
            region: $region !== null
                ? $this->routableLink($region, PageType::AdminDivision, $region->name, $country)
                : null,
            department: $department !== null
                ? $this->routableLink($department, PageType::AdminDivision, $department->name, $country)
                : null,
            activities: $this->activitiesInCity($city, $country),
        );
    }

    public function forAdminDivision(AdminDivision $division, Country $country): AdminDivisionTerritoryLinksData
    {
        return new AdminDivisionTerritoryLinksData(
            parent: $this->adminDivisionParentLink($division, $country),
            children: $division->level === 1
                ? $this->childDivisions($division, $country)
                : $this->citiesInDivision($division, $country),
        );
    }

    /**
     * Lien montant : la division parente si sa route existe déjà, sinon le
     * pays (jamais un lien vers une route pas encore synchronisée,
     * CLAUDE.md §6.5).
     */
    private function adminDivisionParentLink(AdminDivision $division, Country $country): InternalLinkData
    {
        if ($division->parent !== null) {
            $link = $this->routableLink($division->parent, PageType::AdminDivision, $division->parent->name, $country);

            if ($link !== null) {
                return $link;
            }
        }

        return new InternalLinkData(type: PageType::Editorial, label: $country->name, params: ['path' => '/'], path: '/');
    }

    /**
     * @return array<int, InternalLinkData>
     */
    private function childDivisions(AdminDivision $division, Country $country): array
    {
        return $division->children
            ->take(self::MAX_CHILDREN)
            ->map(fn (AdminDivision $child) => $this->routableLink($child, PageType::AdminDivision, $child->name, $country))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, InternalLinkData>
     */
    private function citiesInDivision(AdminDivision $division, Country $country): array
    {
        return $division->cities()
            ->limit(self::MAX_CHILDREN)
            ->get()
            ->map(fn (City $city) => $this->routableLink($city, PageType::City, $city->name, $country))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Ne lie que vers une route déjà synchronisée pour cette entité — jamais
     * un lien mort (CLAUDE.md §6.5). Filtré par pays : indispensable pour
     * `Sector`, entité transversale qui a une route par pays actif (une
     * même paire `entity_type`/`entity_id` correspondrait sinon à
     * plusieurs lignes, et pourrait remonter la route d'un autre pays).
     */
    private function routableLink(Model $entity, PageType $pageType, string $label, Country $country): ?InternalLinkData
    {
        $route = PageRoute::query()
            ->where('country_id', $country->id)
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', $entity->getKey())
            ->where('page_type', $pageType)
            ->first();

        if ($route === null) {
            return null;
        }

        return new InternalLinkData(type: $pageType, label: $label, params: ['slug' => $entity->getAttribute('slug')], path: $route->path);
    }

    public function forActivity(Activity $activity, Country $country): ActivityTerritoryLinksData
    {
        return new ActivityTerritoryLinksData(
            parent: $activity->parent !== null
                ? $this->routableLink($activity->parent, PageType::Activity, $activity->parent->public_label, $country)
                : null,
            sectors: $activity->sectors
                ->take(self::MAX_ACTIVITIES)
                ->map(fn (Sector $sector) => $this->routableLink($sector, PageType::Sector, $sector->name, $country))
                ->filter()
                ->values()
                ->all(),
            cities: $this->citiesForActivity($activity, $country),
        );
    }

    /**
     * Villes où cette activité est déjà pratiquée par au moins une
     * entreprise — lien vers la page activité×ville (Phase 12)
     * correspondante, uniquement si déjà synchronisée.
     *
     * @return array<int, InternalLinkData>
     */
    private function citiesForActivity(Activity $activity, Country $country): array
    {
        $cityIds = Company::query()
            ->where('activity_id', $activity->id)
            ->where('country_id', $country->id)
            ->whereNotNull('city_id')
            ->distinct()
            ->pluck('city_id');

        return City::query()
            ->whereIn('id', $cityIds)
            ->orderBy('name')
            ->limit(self::MAX_ACTIVITIES)
            ->get()
            ->map(function (City $city) use ($activity, $country) {
                $path = $this->buildActivityCityPath->execute($city, $activity, $country);

                $exists = PageRoute::query()->where('country_id', $country->id)->where('path', $path)->exists();

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

    public function forCountry(Country $country): CountryTerritoryLinksData
    {
        return new CountryTerritoryLinksData(
            regions: AdminDivision::query()
                ->where('country_id', $country->id)
                ->where('level', 1)
                ->orderBy('name')
                ->limit(self::MAX_CHILDREN)
                ->get()
                ->map(fn (AdminDivision $region) => $this->routableLink($region, PageType::AdminDivision, $region->name, $country))
                ->filter()
                ->values()
                ->all(),
            activities: Activity::query()
                ->whereHas('nomenclature', fn ($query) => $query->where('country_id', $country->id))
                ->where('is_publishable', true)
                ->orderBy('public_label')
                ->limit(self::MAX_ACTIVITIES)
                ->get()
                ->map(fn (Activity $activity) => $this->routableLink($activity, PageType::Activity, $activity->public_label, $country))
                ->filter()
                ->values()
                ->all(),
        );
    }

    public function forSector(Sector $sector, Country $country): SectorTerritoryLinksData
    {
        return new SectorTerritoryLinksData(
            activities: $sector->activities
                ->take(self::MAX_ACTIVITIES)
                ->map(fn (Activity $activity) => $this->routableLink($activity, PageType::Activity, $activity->public_label, $country))
                ->filter()
                ->values()
                ->all(),
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
