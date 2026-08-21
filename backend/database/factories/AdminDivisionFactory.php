<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminDivision>
 */
class AdminDivisionFactory extends Factory
{
    protected $model = AdminDivision::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'parent_id' => null,
            'level' => 1,
            'code' => fake()->unique()->numerify('##'),
            'name' => fake()->state(),
            'slug' => fake()->unique()->slug(),
            'population' => fake()->numberBetween(1_000, 2_000_000),
            'area_km2' => fake()->randomFloat(2, 10, 10_000),
            'path' => null,
        ];
    }
}
