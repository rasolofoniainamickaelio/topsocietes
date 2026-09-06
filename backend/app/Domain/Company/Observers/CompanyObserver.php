<?php

declare(strict_types=1);

namespace App\Domain\Company\Observers;

use App\Domain\Company\Actions\BuildCompanyPathAction;
use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Jobs\ComputeCompanyNearbyPoisJob;
use App\Domain\Geo\Jobs\ResolveCompanyDistrictJob;
use App\Domain\Geo\Models\City;
use App\Domain\Seo\Actions\CreateRedirectAction;
use App\Domain\Seo\Enums\RedirectReason;
use App\Domain\Seo\Services\InternalLinkingService;
use Illuminate\Support\Facades\Cache;

/**
 * Déclenche les recalculs géo (Phase 4) en réaction aux changements
 * pertinents. Suppose que `location` est modifiée via une instance Eloquent
 * (`save()`/`update()`), pas une requête query builder brute qui
 * contournerait les events — c'est le cas de tous les Jobs de ce domaine
 * (`ResolveCompanyDistrictAction` inclus, qui met pourtant à jour
 * `district_id` en requête directe pour ne pas se redéclencher lui-même).
 */
class CompanyObserver
{
    public function __construct(
        private readonly BuildCompanyPathAction $buildPath,
        private readonly CreateRedirectAction $createRedirect,
    ) {}

    /**
     * `created` ne déclenche jamais `isDirty()` côté `updated` (c'est un
     * event Eloquent distinct) : sans ce handler, une entreprise créée
     * avec déjà une ville/position (import, Phase 03) n'aurait jamais son
     * quartier résolu tant qu'elle ne subit pas une mise à jour ultérieure.
     */
    public function created(Company $company): void
    {
        // `location` est une colonne geography brute : non relue en mémoire
        // après un INSERT. `city_id` seul suffit ici comme filtre — c'est
        // `ResolveCompanyDistrictAction` qui revérifie précisément (via un
        // `fresh()`) avant tout traitement réel.
        if ($company->city_id !== null) {
            ResolveCompanyDistrictJob::dispatch($company);
        }
    }

    public function updated(Company $company): void
    {
        if ($company->isDirty(['location', 'city_id'])) {
            ResolveCompanyDistrictJob::dispatch($company);
        }

        if (
            $company->isDirty(['location', 'content_status', 'is_indexable'])
            && $company->content_status === CompanyContentStatus::Published
            && $company->is_indexable
        ) {
            ComputeCompanyNearbyPoisJob::dispatch($company);
        }

        // Maillage interne (Phase 15) : tout champ qui influence un des
        // groupes de liens invalide le cache — recalculé paresseusement à
        // la prochaine lecture, jamais de recalcul synchrone ici.
        if ($company->isDirty(['legal_name', 'slug', 'activity_id', 'city_id', 'admin_division_id', 'content_status', 'is_indexable'])) {
            Cache::forget(InternalLinkingService::cacheKey($company));
        }

        // Slug immuable en principe (CLAUDE.md §6.4), mais éditable en
        // back-office : tout changement crée automatiquement sa 301, plutôt
        // que de compter sur un geste manuel toujours oublié un jour.
        if ($company->isDirty('slug')) {
            $this->redirectOldCompanyPath($company);
        }
    }

    private function redirectOldCompanyPath(Company $company): void
    {
        $oldCitySlug = $company->isDirty('city_id')
            ? City::query()->find($company->getOriginal('city_id'))?->slug
            : null;

        $oldPath = $this->buildPath->execute($company, slugOverride: (string) $company->getOriginal('slug'), citySlugOverride: $oldCitySlug);
        $newPath = $this->buildPath->execute($company);

        $this->createRedirect->execute($company->country, $oldPath, $newPath, RedirectReason::SlugChange);
    }
}
