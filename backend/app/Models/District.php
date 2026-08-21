<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocation;
use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory;

    use HasLocation;

    protected $fillable = [
        'country_id',
        'city_id',
        'code',
        'name',
        'slug',
        'population',
        'area_km2',
        'companies_count',
        'counts_updated_at',
        'has_local_content',
    ];

    protected function casts(): array
    {
        return [
            'population' => 'integer',
            'area_km2' => 'decimal:2',
            'companies_count' => 'integer',
            'counts_updated_at' => 'datetime',
            'has_local_content' => 'boolean',
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

    /** @return HasMany<DistrictContent, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(DistrictContent::class);
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

    /** @return MorphMany<AiGenerationJob, $this> */
    public function generationJobs(): MorphMany
    {
        return $this->morphMany(AiGenerationJob::class, 'target');
    }
}
