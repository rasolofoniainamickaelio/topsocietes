<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\SourceProvider;
use App\Domain\Geo\Models\Country;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'name',
        'base_url',
        'license',
        'country_id',
        'reliability_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'provider' => SourceProvider::class,
            'reliability_score' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<SourceDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(SourceDocument::class);
    }

    /** @return HasMany<Fact, $this> */
    public function facts(): HasMany
    {
        return $this->hasMany(Fact::class);
    }
}
