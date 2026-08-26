<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyNearbyPoi;
use App\Domain\Geo\Queries\NearbyPointsQuery;
use Illuminate\Support\Facades\DB;

/**
 * Régénère le cache du bloc de proximité (docs/DATABASE.md §4-D) : jamais
 * de `ST_DWithin` à l'affichage à cette volumétrie.
 */
class ComputeCompanyNearbyPoisAction
{
    private const RADIUS_METERS = 20_000;

    private const LIMIT = 25;

    public function __construct(private readonly NearbyPointsQuery $nearbyPointsQuery) {}

    public function execute(Company $company): void
    {
        // `latitude`/`longitude` sont des colonnes générées côté Postgres :
        // absentes de l'instance en mémoire tant qu'elle n'a pas été
        // relue depuis la base (l'INSERT d'Eloquent ne les rapatrie pas).
        $company = $company->fresh();

        if (
            $company === null
            || $company->content_status !== CompanyContentStatus::Published
            || ! $company->is_indexable
            || $company->latitude === null
            || $company->longitude === null
        ) {
            return;
        }

        $pois = $this->nearbyPointsQuery->execute(
            (float) $company->latitude,
            (float) $company->longitude,
            self::RADIUS_METERS,
            self::LIMIT,
        );

        $now = now();
        $rows = $pois->values()
            ->map(fn ($poi, int $index): array => [
                'company_id' => $company->id,
                'poi_id' => $poi->id,
                'distance_m' => (int) $poi->distance_m,
                'rank' => $index + 1,
                'generated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::transaction(function () use ($company, $rows): void {
            CompanyNearbyPoi::query()
                ->where('company_id', $company->id)
                ->whereNotIn('poi_id', array_column($rows, 'poi_id'))
                ->delete();

            if ($rows !== []) {
                CompanyNearbyPoi::query()->upsert(
                    $rows,
                    ['company_id', 'poi_id'],
                    ['distance_m', 'rank', 'generated_at', 'updated_at'],
                );
            }
        });
    }
}
