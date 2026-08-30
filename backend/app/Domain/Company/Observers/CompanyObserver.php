<?php

declare(strict_types=1);

namespace App\Domain\Company\Observers;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Jobs\ComputeCompanyNearbyPoisJob;
use App\Domain\Geo\Jobs\ResolveCompanyDistrictJob;

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
    }
}
