<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\NoindexReason;
use App\Domain\Seo\Enums\PageType;
use Database\Factories\PageRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modélise la table `routes`. Nommé `PageRoute` plutôt que `Route` pour
 * éviter toute collision avec `Illuminate\Support\Facades\Route` /
 * `Illuminate\Routing\Route`, quasi certaine d'être importée dans les
 * mêmes contrôleurs qui manipuleront ce modèle.
 *
 * @property PageType $page_type
 */
class PageRoute extends Model
{
    /** @use HasFactory<PageRouteFactory> */
    use HasFactory;

    protected $table = 'routes';

    protected $fillable = [
        'country_id',
        'path',
        'entity_type',
        'entity_id',
        'page_type',
        'canonical_route_id',
        'is_indexable',
        'noindex_reason',
        'priority',
        'changefreq',
        'last_modified_at',
        'sitemap_shard_id',
    ];

    protected function casts(): array
    {
        return [
            'page_type' => PageType::class,
            'is_indexable' => 'boolean',
            'noindex_reason' => NoindexReason::class,
            'priority' => 'decimal:1',
            'last_modified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<PageRoute, $this> */
    public function canonicalRoute(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_route_id');
    }

    /** @return HasMany<PageRoute, $this> */
    public function alternateRoutes(): HasMany
    {
        return $this->hasMany(self::class, 'canonical_route_id');
    }

    /** @return BelongsTo<SitemapShard, $this> */
    public function sitemapShard(): BelongsTo
    {
        return $this->belongsTo(SitemapShard::class);
    }

    /** @return HasMany<PagePublicationDecision, $this> */
    public function publicationDecisions(): HasMany
    {
        return $this->hasMany(PagePublicationDecision::class, 'route_id');
    }
}
