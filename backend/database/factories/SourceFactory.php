<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SourceProvider;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        return [
            'provider' => SourceProvider::OpenData,
            'name' => fake()->company(),
            'base_url' => fake()->url(),
            'license' => 'ODbL',
            'country_id' => null,
            'reliability_score' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
