<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentSection;
use App\Enums\ContentStatus;
use App\Models\City;
use App\Models\CityContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CityContent>
 */
class CityContentFactory extends Factory
{
    protected $model = CityContent::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
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
