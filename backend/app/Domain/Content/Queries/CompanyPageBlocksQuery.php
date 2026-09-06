<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use Illuminate\Support\Collection;

/**
 * Compose, pour une fiche entreprise, tous les blocs territoriaux et
 * sectoriels pertinents (Phase 08) : quartier→commune→région (ou
 * commune→région si pas de quartier résolu), activité et ses secteurs, et
 * le contenu croisé commune×activité — jamais dupliqué en base, assemblé à
 * la lecture depuis les tables mutualisées.
 */
class CompanyPageBlocksQuery
{
    public function __construct(
        private readonly CityContentBlocksQuery $cityBlocks,
        private readonly DistrictContentBlocksQuery $districtBlocks,
        private readonly ActivityContentBlocksQuery $activityBlocks,
    ) {}

    /** @return Collection<int, mixed> */
    public function execute(Company $company): Collection
    {
        $blocks = collect();

        if ($company->district !== null) {
            $blocks = $blocks->concat($this->districtBlocks->execute($company->district));
        } elseif ($company->city !== null) {
            $blocks = $blocks->concat($this->cityBlocks->execute($company->city));
        }

        if ($company->activity === null) {
            return $blocks;
        }

        $blocks = $blocks->concat($this->activityBlocks->forActivity($company->activity, $company->country));

        foreach ($company->activity->sectors as $sector) {
            $blocks = $blocks->concat($this->activityBlocks->forSector($sector, $company->country));
        }

        if ($company->city !== null) {
            $blocks = $blocks->concat(
                CityActivityContent::query()
                    ->where('city_id', $company->city_id)
                    ->where('activity_id', $company->activity_id)
                    ->where('status', ContentStatus::Published)
                    ->get(),
            );
        }

        return $blocks;
    }
}
