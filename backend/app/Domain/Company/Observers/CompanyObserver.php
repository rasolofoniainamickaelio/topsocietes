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
