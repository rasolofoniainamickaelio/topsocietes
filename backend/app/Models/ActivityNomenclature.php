<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActivityNomenclatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityNomenclature extends Model
{
    /** @use HasFactory<ActivityNomenclatureFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'country_id',
        'version',
    ];

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'nomenclature_id');
    }
}
