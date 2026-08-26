<?php

declare(strict_types=1);

namespace App\Domain\Geo\Queries;

use App\Domain\Geo\Models\PointOfInterest;
use Illuminate\Database\Eloquent\Collection;

/**
 * N POI publiables les plus proches d'un point, dans un rayon donné.
 * `distance_m` vient de `ST_Distance` (HasLocation::scopeOrderByDistanceFrom),
 * jamais calculée applicativement (CLAUDE.md §6).
 */
class NearbyPointsQuery
{
    /** @return Collection<int, PointOfInterest> */
    public function execute(float $lat, float $lng, int $radiusMeters, int $limit): Collection
    {
        return PointOfInterest::query()
            ->where('is_publishable', true)
            ->withinRadius($lat, $lng, $radiusMeters)
            ->orderByDistanceFrom($lat, $lng)
            ->limit($limit)
            ->get();
    }
}
