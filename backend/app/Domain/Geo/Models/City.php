<?php

declare(strict_types=1);

namespace App\Domain\Geo\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\Fact;
use App\Domain\Content\Models\SourceDocument;
use App\Domain\Geo\Observers\CityObserver;
use App\Support\Casts\PostgresArrayCast;
use App\Support\Concerns\HasLocation;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * `latitude`/`longitude` (colonnes générées) et `location`/`boundary`
 * (geography brutes) sont ajoutées par `DB::statement()` dans la
 * migration, hors de portée du scanner de Blueprint de Larastan.
 *
 * @property-read string|null $latitude
 * @property-read string|null $longitude
 * @property-read string|null $location
 * @property-read string|null $boundary
 */
#[ObservedBy(CityObserver::class)]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    use HasLocation;

    protected $fillable = [
        'country_id',
        'admin_division_id',
        'code',
        'name',
        'slug',
        'postal_codes',
        'population',
        'area_km2',
        'altitude',
        'companies_count',
        'counts_updated_at',
        'has_local_content',
        'wikidata_id',
        'wikipedia_title',
    ];

    protected function casts(): array
    {
        return [
            'postal_codes' => PostgresArrayCast::class,
            'population' => 'integer',
            'area_km2' => 'decimal:2',
            'altitude' => 'integer',
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

    /** @return BelongsTo<AdminDivision, $this> */
    public function adminDivision(): BelongsTo
    {
        return $this->belongsTo(AdminDivision::class);
    }

    /** @return HasMany<District, $this> */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /** @return HasMany<CityNeighbor, $this> */
    public function neighborLinks(): HasMany
    {
        return $this->hasMany(CityNeighbor::class)->orderBy('rank');
    }

    /** @return HasMany<CityContent, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(CityContent::class);
    }

    /** @return HasMany<CityActivityContent, $this> */
    public function activityContents(): HasMany
    {
        return $this->hasMany(CityActivityContent::class);
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
