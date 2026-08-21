<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\SitemapShard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitemapShard>
 */
class SitemapShardFactory extends Factory
{
    protected $model = SitemapShard::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'type' => PageType::City,
            'index' => 1,
            'url_count' => fake()->numberBetween(0, 50_000),
            'file_path' => null,
            'generated_at' => null,
            'is_stale' => true,
        ];
    }
}
