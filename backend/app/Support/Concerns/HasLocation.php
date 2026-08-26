<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pour les modèles portant une colonne `location geography(Point,4326)`.
 * Toute distance affichée doit venir de PostGIS (`ST_Distance`), jamais
 * calculée applicativement ni par un LLM (CLAUDE.md §6).
 *
 * @property-read int|null $distance_m Présent uniquement après `orderByDistanceFrom()` (alias par défaut).
 */
trait HasLocation
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, int $meters): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $meters],
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrderByDistanceFrom(Builder $query, float $lat, float $lng, string $alias = 'distance_m'): Builder
    {
        return $query
            ->selectRaw(
                "*, ROUND(ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography))::integer as {$alias}",
                [$lng, $lat],
            )
            ->orderBy($alias);
    }
}
