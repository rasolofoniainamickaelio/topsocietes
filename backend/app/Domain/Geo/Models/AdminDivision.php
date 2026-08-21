<?php

declare(strict_types=1);

namespace App\Domain\Geo\Models;

use Database\Factories\AdminDivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminDivision extends Model
{
    /** @use HasFactory<AdminDivisionFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'parent_id',
        'level',
        'code',
        'name',
        'slug',
        'population',
        'area_km2',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'population' => 'integer',
            'area_km2' => 'decimal:2',
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
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<AdminDivision, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<City, $this> */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
