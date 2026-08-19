<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PoiCategory;
use App\Enums\PoiProvider;
use App\Models\Concerns\HasLocation;
use Database\Factories\PointOfInterestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointOfInterest extends Model
{
    /** @use HasFactory<PointOfInterestFactory> */
    use HasFactory;

    use HasLocation;
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'city_id',
        'district_id',
        'external_ref',
        'provider',
        'name',
        'category',
        'subcategory',
        'description',
        'attributes',
        'editorial_score',
        'is_publishable',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PoiProvider::class,
            'category' => PoiCategory::class,
            'attributes' => 'array',
            'editorial_score' => 'integer',
            'is_publishable' => 'boolean',
            'last_synced_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<City, $this> */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** @return BelongsTo<District, $this> */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /** @return HasMany<CompanyNearbyPoi, $this> */
    public function nearbyCompanies(): HasMany
    {
        return $this->hasMany(CompanyNearbyPoi::class, 'poi_id');
    }

    /** @return MorphMany<Fact, $this> */
    public function facts(): MorphMany
    {
        return $this->morphMany(Fact::class, 'subject');
    }

    /** @return MorphMany<SourceDocument, $this> */
    public function sourceDocuments(): MorphMany
    {
        return $this->morphMany(SourceDocument::class, 'subject');
    }
}
