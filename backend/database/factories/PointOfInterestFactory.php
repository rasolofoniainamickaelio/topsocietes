<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PoiCategory;
use App\Enums\PoiProvider;
use App\Models\Country;
use App\Models\PointOfInterest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<PointOfInterest>
 */
class PointOfInterestFactory extends Factory
{
    protected $model = PointOfInterest::class;

    public function definition(): array
    {
        $lat = fake()->latitude(-90, 90);
        $lng = fake()->longitude(-180, 180);

        return [
            'country_id' => Country::factory(),
            'city_id' => null,
            'district_id' => null,
            'external_ref' => 'osm:node/'.fake()->unique()->numberBetween(1, 999_999_999),
            'provider' => PoiProvider::OpenStreetMap,
            'name' => fake()->company(),
            'category' => PoiCategory::ToSee,
            'subcategory' => null,
            'description' => fake()->sentence(),
            'attributes' => [],
            'editorial_score' => fake()->numberBetween(0, 100),
            'is_publishable' => false,
            'last_synced_at' => now(),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography"),
        ];
    }
}
