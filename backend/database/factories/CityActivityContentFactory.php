<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CityActivityContent>
 */
class CityActivityContentFactory extends Factory
{
    protected $model = CityActivityContent::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'activity_id' => Activity::factory(),
            'sector_id' => null,
            'locale' => 'fr',
            'section' => ContentSection::LocalOverview->value,
            'body' => fake()->paragraphs(3, true),
            'data' => [],
            'status' => ContentStatus::Draft,
            'companies_count_at_generation' => fake()->numberBetween(0, 500),
            'generation_id' => null,
            'published_at' => null,
        ];
    }

    /** Bascule vers la branche `sector_id` du XOR (utile pour les tests de contrainte). */
    public function forSector(): static
    {
        return $this->state(fn (): array => [
            'activity_id' => null,
            'sector_id' => Sector::factory(),
        ]);
    }
}
