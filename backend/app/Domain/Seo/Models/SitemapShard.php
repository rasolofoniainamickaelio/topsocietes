<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use Database\Factories\SitemapShardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property PageType $type
 * @property Carbon|null $generated_at
 */
class SitemapShard extends Model
{
    /** @use HasFactory<SitemapShardFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'type',
        'index',
        'url_count',
        'file_path',
        'generated_at',
        'is_stale',
    ];

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'index' => 'integer',
            'url_count' => 'integer',
            'generated_at' => 'datetime',
            'is_stale' => 'boolean',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<PageRoute, $this> */
    public function routes(): HasMany
    {
        return $this->hasMany(PageRoute::class);
    }
}
