<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityMapping>
 */
class ActivityMappingFactory extends Factory
{
    protected $model = ActivityMapping::class;

    public function definition(): array
    {
        return [
            'from_activity_id' => Activity::factory(),
            'to_activity_id' => Activity::factory(),
            'confidence' => fake()->numberBetween(50, 100),
        ];
    }
}
