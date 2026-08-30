<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Geo\Models\AdminDivision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminDivisionContent>
 */
class AdminDivisionContentFactory extends Factory
{
    protected $model = AdminDivisionContent::class;

    public function definition(): array
    {
        return [
            'admin_division_id' => AdminDivision::factory(),
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
