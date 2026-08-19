<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyNearbyPoi;
use App\Models\PointOfInterest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyNearbyPoi>
 */
class CompanyNearbyPoiFactory extends Factory
{
    protected $model = CompanyNearbyPoi::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'poi_id' => PointOfInterest::factory(),
            'distance_m' => fake()->numberBetween(10, 20_000),
            'rank' => fake()->numberBetween(1, 30),
            'generated_at' => now(),
        ];
    }
}
