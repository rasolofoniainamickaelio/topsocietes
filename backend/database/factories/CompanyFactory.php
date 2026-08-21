<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Enums\CompanyStatus;
use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'national_id' => fake()->unique()->numerify('#########'),
            'legal_name' => fake()->company(),
            'trade_name' => null,
            'slug' => fake()->unique()->slug(),
            'legal_form_code' => null,
            'legal_form_label' => null,
            'status' => CompanyStatus::Active,
            'created_date' => fake()->date(),
            'ceased_date' => null,
            'activity_id' => null,
            'activity_code_raw' => fake()->bothify('##.##?'),
            'headcount_range' => null,
            'city_id' => null,
            'district_id' => null,
            'admin_division_id' => null,
            'geocoding_status' => GeocodingStatus::Pending,
            'content_status' => CompanyContentStatus::Pending,
            'is_indexable' => true,
            'about_text' => null,
            'data_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
