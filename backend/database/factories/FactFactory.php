<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Models\Fact;
use App\Domain\Content\Models\Source;
use App\Domain\Geo\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fact>
 */
class FactFactory extends Factory
{
    protected $model = Fact::class;

    public function definition(): array
    {
        return [
            'subject_type' => (new City)->getMorphClass(),
            'subject_id' => City::factory(),
            'key' => 'population',
            'value' => (string) fake()->numberBetween(1_000, 500_000),
            'value_json' => null,
            'source_id' => Source::factory(),
            'source_url' => fake()->url(),
            'date_retrieved' => now(),
            'confidence_score' => fake()->numberBetween(60, 100),
            'corroboration_count' => 1,
            'last_verified_at' => now(),
        ];
    }
}
