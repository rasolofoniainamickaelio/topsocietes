<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Company\Actions\BuildCompanyPathAction;
use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Queries\NearbyCompaniesQuery;
use App\Domain\Seo\Data\CompanyLinksData;
use App\Domain\Seo\Data\InternalLinkData;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Support\Facades\Cache;

/**
 * Source unique de vérité du maillage interne (Phase 15) — jamais dupliquée
 * dans un contrôleur ou une Resource. Résultats mis en cache (24h, largement
 * au-delà du rythme de mise à jour d'une fiche) par `CompanyObserver`, qui
 * invalide la clé aux mêmes changements que ceux qui affectent son contenu.
 * Déterministe : mêmes entrées → mêmes liens, toujours triés et plafonnés.
 */
class InternalLinkingService
{
    private const MAX_PER_GROUP = 6;

    private const CACHE_TTL_SECONDS = 86_400;

    public function __construct(
        private readonly NearbyCompaniesQuery $nearbyCompanies,
        private readonly BuildCompanyPathAction $buildCompanyPath,
    ) {}

    public function forCompany(Company $company): CompanyLinksData
    {
        return Cache::remember(
            self::cacheKey($company),
            self::CACHE_TTL_SECONDS,
            fn () => $this->build($company),
        );
    }

    public static function cacheKey(Company $company): string
    {
        return "internal-links:company:{$company->id}";
    }

    private function build(Company $company): CompanyLinksData
    {
        $department = $company->adminDivision;
        $region = $department?->parent;

        return new CompanyLinksData(
            sameTradeInCity: $this->sameTradeInCity($company),
            nearby: $this->nearby($company),
            activityInCity: $this->activityInCity($company),
            activityInNeighborCities: $this->activityInNeighborCities($company),
            department: $this->adminDivisionLink($department),
            region: $this->adminDivisionLink($region),
            country: $this->countryLink($company->country),
            relatedActivities: $this->relatedActivities($company),
            companyCreation: $this->companyCreationLink($company->country),
        );
    }

    /**
     * "Autres sociétés du même métier" : même activité, même commune.
     *
     * @return array<int, InternalLinkData>
     */
    private function sameTradeInCity(Company $company): array
    {
        if ($company->activity_id === null || $company->city_id === null) {
            return [];
        }

        return Company::query()
            ->where('city_id', $company->city_id)
            ->where('activity_id', $company->activity_id)
            ->where('id', '!=', $company->id)
            ->where('content_status', CompanyContentStatus::Published)
            ->where('is_indexable', true)
            ->orderBy('legal_name')
            ->limit(self::MAX_PER_GROUP)
            ->with('city')
            ->get()
            ->filter(fn (Company $other) => $this->isIndexable(PageType::Company, $other->id))
            ->map(fn (Company $other) => new InternalLinkData(
                type: PageType::Company,
                label: $other->legal_name,
                params: ['slug' => $other->slug],
                path: $this->buildCompanyPath->execute($other),
            ))
            ->values()
            ->all();
    }

    /**
     * "Sociétés proches géographiquement" : toute activité, dans un rayon
     * (réutilise `NearbyCompaniesQuery`, Phase 04 — jamais un second calcul
     * de proximité ici).
     *
     * @return array<int, InternalLinkData>
     */
    private function nearby(Company $company): array
    {
        return $this->nearbyCompanies->execute($company, limit: self::MAX_PER_GROUP)
            ->filter(fn (Company $other) => $this->isIndexable(PageType::Company, $other->id))
            ->map(fn (Company $other) => new InternalLinkData(
                type: PageType::Company,
                label: $other->legal_name,
                params: ['slug' => $other->slug],
                path: $this->buildCompanyPath->execute($other),
            ))
            ->values()
            ->all();
    }

    /**
     * "Le métier dans la ville" : un lien unique vers la page croisée
     * activité×commune, jamais une liste d'entreprises.
     */
    private function activityInCity(Company $company): ?InternalLinkData
    {
        if ($company->activity === null || $company->city === null) {
            return null;
        }

        return $this->activityCityLink($company->activity, $company->city);
    }

    /**
     * "Le métier dans les villes voisines" : même page croisée que
     * ci-dessus, pour chaque commune limitrophe pré-calculée (Phase 04,
     * `CityNeighbor` — jamais un calcul de proximité ici).
     *
     * @return array<int, InternalLinkData>
     */
    private function activityInNeighborCities(Company $company): array
    {
        if ($company->activity === null || $company->city === null) {
            return [];
        }

        return $company->city->neighborLinks()
            ->with('neighborCity')
            ->limit(self::MAX_PER_GROUP)
            ->get()
            ->map(fn (CityNeighbor $link) => $link->neighborCity)
            ->filter()
            ->map(fn (City $neighborCity) => $this->activityCityLink($company->activity, $neighborCity))
            ->values()
            ->all();
    }

    private function activityCityLink(Activity $activity, City $city): InternalLinkData
    {
        return new InternalLinkData(
            type: PageType::ActivityCity,
            label: "{$activity->public_label} à {$city->name}",
            params: ['citySlug' => $city->slug, 'activitySlug' => $activity->slug],
        );
    }

    private function adminDivisionLink(?AdminDivision $division): ?InternalLinkData
    {
        if ($division === null || ! $this->isIndexable(PageType::AdminDivision, $division->id)) {
            return null;
        }

        return new InternalLinkData(
            type: PageType::AdminDivision,
            label: $division->name,
            params: ['slug' => $division->slug],
        );
    }

    private function countryLink(Country $country): InternalLinkData
    {
        return new InternalLinkData(
            type: PageType::Editorial,
            label: $country->name,
            params: ['countryCode' => $country->code],
        );
    }

    /**
     * "Activités connexes" : les activités sœurs dans la même branche de
     * nomenclature (même `parent_id`) — pas `ActivityMapping`, qui relie des
     * codes équivalents entre nomenclatures de pays différents, un besoin
     * distinct.
     *
     * @return array<int, InternalLinkData>
     */
    private function relatedActivities(Company $company): array
    {
        if ($company->activity === null || $company->activity->parent_id === null) {
            return [];
        }

        return Activity::query()
            ->where('parent_id', $company->activity->parent_id)
            ->where('id', '!=', $company->activity_id)
            ->where('is_publishable', true)
            ->orderBy('public_label')
            ->limit(self::MAX_PER_GROUP)
            ->get()
            ->filter(fn (Activity $related) => $this->isIndexable(PageType::Activity, $related->id))
            ->map(fn (Activity $related) => new InternalLinkData(
                type: PageType::Activity,
                label: $related->public_label,
                params: ['slug' => $related->slug],
            ))
            ->values()
            ->all();
    }

    /**
     * "Pages de création d'entreprise" : une page éditoriale fixe,
     * configurable par pays (`countries.url_patterns`, CLAUDE.md §6.6),
     * jamais codée en dur. Absente tant qu'aucun pays ne la configure.
     */
    private function companyCreationLink(Country $country): ?InternalLinkData
    {
        $path = $country->url_patterns['company_creation'] ?? null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return new InternalLinkData(
            type: PageType::Editorial,
            label: 'Créer mon entreprise',
            params: ['path' => $path],
        );
    }

    /**
     * Ne lie jamais vers une page explicitement non indexable (Phase 18).
     * Tant que `routes` n'est peuplée par aucune Action (avant la Phase 18),
     * l'absence de ligne vaut "indexable" — jamais "à exclure par défaut".
     * Les pages composites (activity_city) n'ont pas d'`entity_id` unique
     * exploitable ici et ne sont donc pas vérifiées par ce filtre.
     */
    private function isIndexable(PageType $type, int $entityId): bool
    {
        $route = PageRoute::query()
            ->where('entity_type', $type->value)
            ->where('entity_id', $entityId)
            ->first();

        return $route === null || $route->is_indexable;
    }
}
