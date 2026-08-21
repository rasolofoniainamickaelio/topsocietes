<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityContent>
 */
class ActivityContentFactory extends Factory
{
    protected $model = ActivityContent::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'sector_id' => null,
            'country_id' => null,
            'locale' => 'fr',
            'section' => ContentSection::UnderstandingSector->value,
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'data' => [],
            'status' => ContentStatus::Draft,
            'quality_score' => fake()->numberBetween(0, 100),
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
