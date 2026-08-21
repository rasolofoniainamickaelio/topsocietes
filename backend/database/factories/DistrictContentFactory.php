<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\DistrictContent;
use App\Domain\Geo\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DistrictContent>
 */
class DistrictContentFactory extends Factory
{
    protected $model = DistrictContent::class;

    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'locale' => 'fr',
            'section' => ContentSection::History->value,
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'data' => [],
            'status' => ContentStatus::Draft,
            'quality_score' => fake()->numberBetween(0, 100),
            'generation_id' => null,
            'published_at' => null,
        ];
    }
}
