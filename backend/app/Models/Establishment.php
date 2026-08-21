<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\GeocodingStatus;
use App\Models\Concerns\HasLocation;
use Database\Factories\EstablishmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Establishment extends Model
{
    /** @use HasFactory<EstablishmentFactory> */
    use HasFactory;

    use HasLocation;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'country_id',
        'national_id',
        'is_headquarters',
        'street_number',
        'street_name',
        'address_line2',
        'postal_code',
        'city_id',
        'district_id',
        'geocoding_status',
        'status',
        'activity_id',
    ];

    protected function casts(): array
    {
        return [
            'is_headquarters' => 'boolean',
            'geocoding_status' => GeocodingStatus::class,
            'status' => CompanyStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
