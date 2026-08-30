<?php

declare(strict_types=1);

namespace App\Domain\Geo\Queries;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Collection;

/**
 * N entreprises publiables les plus proches d'une entreprise donnée, dans
 * un rayon donné. `distance_m` vient de `ST_Distance`
 * (HasLocation::scopeOrderByDistanceFrom), jamais calculée
 * applicativement (CLAUDE.md §6). Même pays uniquement : la proximité n'a
 * aucun sens en travers une frontière multi-pays mutualisée.
 */
class NearbyCompaniesQuery
{
    /** @return Collection<int, Company> */
    public function execute(Company $company, int $radiusMeters = 20_000, int $limit = 10): Collection
    {
        if ($company->latitude === null || $company->longitude === null) {
            return new Collection;
        }

        return Company::query()
            ->where('country_id', $company->country_id)
            ->where('id', '!=', $company->id)
            ->where('content_status', CompanyContentStatus::Published)
            ->where('is_indexable', true)
            ->withinRadius((float) $company->latitude, (float) $company->longitude, $radiusMeters)
            ->orderByDistanceFrom((float) $company->latitude, (float) $company->longitude)
            ->limit($limit)
            ->get();
    }
}
