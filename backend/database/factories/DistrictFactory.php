<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'city_id' => City::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->streetName(),
            'slug' => fake()->unique()->slug(),
            'population' => fake()->numberBetween(100, 50_000),
            'area_km2' => fake()->randomFloat(2, 0.1, 20),
            'companies_count' => 0,
            'counts_updated_at' => null,
            'has_local_content' => false,
        ];
    }
}
