<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Models\ContentSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentSection>
 */
class ContentSectionFactory extends Factory
{
    protected $model = ContentSection::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'label' => fake()->words(3, true),
            'scope' => ContentSectionScope::City,
            'is_enabled' => true,
            'min_facts_required' => 0,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }
}
