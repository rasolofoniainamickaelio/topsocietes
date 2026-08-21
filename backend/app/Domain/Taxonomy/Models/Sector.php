<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Models;

use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\CityActivityContent;
use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'companies_count',
        'counts_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'companies_count' => 'integer',
            'counts_updated_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Activity, $this> */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_sector');
    }

    /** @return HasMany<ActivityContent, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(ActivityContent::class);
    }

    /** @return HasMany<CityActivityContent, $this> */
    public function cityActivityContents(): HasMany
    {
        return $this->hasMany(CityActivityContent::class);
    }
}
