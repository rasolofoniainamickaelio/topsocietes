<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyNearbyPoiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cache pré-calculé du bloc de proximité (voir migration). Jamais généré
 * ni lu par un calcul géographique à l'affichage.
 */
class CompanyNearbyPoi extends Model
{
    /** @use HasFactory<CompanyNearbyPoiFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'poi_id',
        'distance_m',
        'rank',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'distance_m' => 'integer',
            'rank' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<PointOfInterest, $this> */
    public function poi(): BelongsTo
    {
        return $this->belongsTo(PointOfInterest::class, 'poi_id');
    }
}
