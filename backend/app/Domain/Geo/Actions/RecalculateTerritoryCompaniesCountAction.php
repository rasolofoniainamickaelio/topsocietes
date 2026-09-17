<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\District;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;

/**
 * Recalcule le compteur d'entreprises dénormalisé d'un territoire ou d'une
 * activité/secteur (`companies_count`/`counts_updated_at`), jamais calculé
 * à l'affichage (CLAUDE.md §3, config/horizon.php). Un secteur n'a pas de
 * colonne directe sur `companies` : l'agrégation passe par la table pivot
 * `activity_sector`.
 */
class RecalculateTerritoryCompaniesCountAction
{
    public function execute(PageType $type, int $entityId): void
    {
        $sector = $type === PageType::Sector ? Sector::find($entityId) : null;

        [$model, $count] = match ($type) {
            PageType::City => [City::find($entityId), Company::query()->where('city_id', $entityId)->count()],
            PageType::District => [District::find($entityId), Company::query()->where('district_id', $entityId)->count()],
            PageType::AdminDivision => [AdminDivision::find($entityId), Company::query()->where('admin_division_id', $entityId)->count()],
            PageType::Activity => [Activity::find($entityId), Company::query()->where('activity_id', $entityId)->count()],
            PageType::Sector => [$sector, $this->countForSector($sector)],
            default => [null, 0],
        };

        $model?->update(['companies_count' => $count, 'counts_updated_at' => now()]);
    }

    private function countForSector(?Sector $sector): int
    {
        if ($sector === null) {
            return 0;
        }

        $activityIds = $sector->activities()->pluck('activities.id');

        return Company::query()->whereIn('activity_id', $activityIds)->count();
    }
}
