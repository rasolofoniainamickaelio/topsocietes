<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        $lat = fake()->latitude(-90, 90);
        $lng = fake()->longitude(-180, 180);

        return [
            'country_id' => Country::factory(),
            'admin_division_id' => null,
            'code' => fake()->unique()->numerify('#####'),
            'name' => fake()->city(),
            'slug' => fake()->unique()->slug(),
            'postal_codes' => [fake()->postcode()],
            'population' => fake()->numberBetween(500, 500_000),
            'area_km2' => fake()->randomFloat(2, 1, 500),
            'altitude' => fake()->numberBetween(0, 2_000),
            'companies_count' => 0,
            'counts_updated_at' => null,
            'has_local_content' => false,
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography"),
        ];
    }
}
