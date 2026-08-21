<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageRoute>
 */
class PageRouteFactory extends Factory
{
    protected $model = PageRoute::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'path' => '/'.fake()->unique()->slug(),
            'entity_type' => null,
            'entity_id' => null,
            'page_type' => PageType::City,
            'canonical_route_id' => null,
            'is_indexable' => true,
            'noindex_reason' => null,
            'priority' => 0.5,
            'changefreq' => null,
            'last_modified_at' => now(),
            'sitemap_shard_id' => null,
        ];
    }
}
