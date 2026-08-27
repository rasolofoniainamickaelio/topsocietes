<?php

declare(strict_types=1);

namespace App\Domain\Company\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Enums\CompanyStatus;
use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Observers\CompanyObserver;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Moderation\Models\DisputeReport;
use App\Domain\Taxonomy\Models\Activity;
use App\Support\Concerns\HasLocation;
use App\Support\Concerns\HasPublicId;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `latitude`/`longitude` (colonnes générées) et `location` (geography brute)
 * sont ajoutées par `DB::statement()` dans la migration, hors de portée du
 * scanner de Blueprint de Larastan — déclarées ici pour qu'il les connaisse.
 *
 * @property-read string|null $latitude
 * @property-read string|null $longitude
 * @property-read string|null $location
 * @property CompanyContentStatus $content_status
 */
#[ObservedBy(CompanyObserver::class)]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasLocation;
    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'national_id',
        'legal_name',
        'trade_name',
        'slug',
        'legal_form_code',
        'legal_form_label',
        'status',
        'created_date',
        'ceased_date',
        'activity_id',
        'activity_code_raw',
        'headcount_range',
        'main_establishment_id',
        'city_id',
        'district_id',
        'admin_division_id',
        'location',
        'geocoding_status',
        'content_status',
        'is_indexable',
        'source_batch_id',
        'about_text',
        'about_generation_id',
        'data_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'geocoding_status' => GeocodingStatus::class,
            'content_status' => CompanyContentStatus::class,
            'created_date' => 'date',
            'ceased_date' => 'date',
            'is_indexable' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
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

    /** @return BelongsTo<AdminDivision, $this> */
    public function adminDivision(): BelongsTo
    {
        return $this->belongsTo(AdminDivision::class);
    }

    /** @return BelongsTo<Establishment, $this> */
    public function mainEstablishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'main_establishment_id');
    }

    /** @return HasMany<Establishment, $this> */
    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }

    /** @return HasMany<CompanyContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(CompanyContact::class);
    }

    /** @return HasMany<CompanyNearbyPoi, $this> */
    public function nearbyPois(): HasMany
    {
        return $this->hasMany(CompanyNearbyPoi::class)->orderBy('rank');
    }

    /** @return MorphMany<Fact, $this> */
    public function facts(): MorphMany
    {
        return $this->morphMany(Fact::class, 'subject');
    }

    /** @return MorphMany<AiGenerationJob, $this> */
    public function generationJobs(): MorphMany
    {
        return $this->morphMany(AiGenerationJob::class, 'target');
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function aboutGeneration(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'about_generation_id');
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function sourceBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'source_batch_id');
    }

    /** @return HasMany<CompanyClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(CompanyClaim::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<DisputeReport, $this> */
    public function disputeReports(): HasMany
    {
        return $this->hasMany(DisputeReport::class);
    }

    /** @return HasMany<ContactVisibilityEvent, $this> */
    public function contactVisibilityEvents(): HasMany
    {
        return $this->hasMany(ContactVisibilityEvent::class);
    }
}
