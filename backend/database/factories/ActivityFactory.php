<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityNomenclature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'nomenclature_id' => ActivityNomenclature::factory(),
            'parent_id' => null,
            'level' => 1,
            'code' => fake()->unique()->bothify('##.##?'),
            'label' => fake()->sentence(),
            'public_label' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'is_publishable' => true,
            'companies_count' => 0,
            'counts_updated_at' => null,
        ];
    }
}
