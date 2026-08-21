<?php

declare(strict_types=1);

namespace App\Domain\Geo\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'iso_alpha2',
        'name',
        'subdomain',
        'default_locale',
        'currency',
        'timezone',
        'is_active',
        'admin_level_labels',
        'identifier_config',
        'activity_nomenclature_code',
        'url_patterns',
        'source_config',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'admin_level_labels' => 'array',
            'identifier_config' => 'array',
            'url_patterns' => 'array',
            'source_config' => 'array',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<AdminDivision, $this> */
    public function adminDivisions(): HasMany
    {
        return $this->hasMany(AdminDivision::class);
    }

    /** @return HasMany<City, $this> */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /** @return HasMany<District, $this> */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}
