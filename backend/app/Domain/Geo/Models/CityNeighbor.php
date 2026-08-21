<?php

declare(strict_types=1);

namespace App\Domain\Geo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cache pré-calculé des communes voisines. Jamais généré ni lu par un
 * calcul de proximité à l'affichage (voir migration).
 */
class CityNeighbor extends Model
{
    protected $fillable = [
        'city_id',
        'neighbor_city_id',
        'distance_m',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'distance_m' => 'integer',
            'rank' => 'integer',
        ];
    }

    /** @return BelongsTo<City, $this> */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** @return BelongsTo<City, $this> */
    public function neighborCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'neighbor_city_id');
    }
}
