<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use Illuminate\Support\Facades\DB;

/**
 * Régénère `city_neighbors` (docs/DATABASE.md §4-A) : contiguïté du
 * `boundary` d'abord, complétée par la distance des centroïdes jusqu'à
 * MAX_NEIGHBORS, classement final par distance réelle sur l'ensemble
 * retenu.
 */
class ComputeCityNeighborsAction
{
    private const MAX_NEIGHBORS = 8;

    public function execute(City $city): void
    {
        // `latitude`/`longitude`/`boundary` sont des colonnes générées ou
        // ajoutées côté Postgres : absentes de l'instance en mémoire tant
        // qu'elle n'a pas été relue depuis la base (l'INSERT d'Eloquent ne
        // les rapatrie pas).
        $city = $city->fresh();

        if ($city === null) {
            return;
        }

        $contiguous = collect();

        if ($city->boundary !== null) {
            $contiguous = City::query()
                ->where('country_id', $city->country_id)
                ->where('id', '!=', $city->id)
                ->whereNotNull('boundary')
                ->whereRaw('ST_Intersects(boundary::geometry, (SELECT boundary FROM cities WHERE id = ?)::geometry)', [$city->id])
                ->orderByDistanceFrom((float) $city->latitude, (float) $city->longitude)
                ->get();
        }

        $missing = self::MAX_NEIGHBORS - $contiguous->count();

        $filled = $missing > 0
            ? City::query()
                ->where('country_id', $city->country_id)
                ->where('id', '!=', $city->id)
                ->whereNotIn('id', $contiguous->pluck('id'))
                ->orderByDistanceFrom((float) $city->latitude, (float) $city->longitude)
                ->limit($missing)
                ->get()
            : collect();

        $neighbors = $contiguous->concat($filled)
            ->sortBy('distance_m')
            ->values()
            ->take(self::MAX_NEIGHBORS);

        $now = now();
        $rows = $neighbors->values()
            ->map(fn ($neighbor, int $index): array => [
                'city_id' => $city->id,
                'neighbor_city_id' => $neighbor->id,
                'distance_m' => (int) $neighbor->distance_m,
                'rank' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::transaction(function () use ($city, $rows): void {
            CityNeighbor::query()
                ->where('city_id', $city->id)
                ->whereNotIn('neighbor_city_id', array_column($rows, 'neighbor_city_id'))
                ->delete();

            if ($rows !== []) {
                CityNeighbor::query()->upsert(
                    $rows,
                    ['city_id', 'neighbor_city_id'],
                    ['distance_m', 'rank', 'updated_at'],
                );
            }
        });
    }
}
