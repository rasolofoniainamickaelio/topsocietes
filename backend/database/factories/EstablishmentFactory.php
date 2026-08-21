<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Company\Enums\CompanyStatus;
use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Establishment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Establishment>
 */
class EstablishmentFactory extends Factory
{
    protected $model = Establishment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            // country_id dérivé de l'entreprise plutôt qu'un Country::factory()
            // indépendant, pour ne pas produire une incohérence (établissement
            // dans un pays différent de son entreprise).
            'country_id' => fn (array $attributes) => Company::find($attributes['company_id'])->country_id,
            'national_id' => fake()->unique()->numerify('##############'),
            'is_headquarters' => false,
            'street_number' => fake()->buildingNumber(),
            'street_name' => fake()->streetName(),
            'address_line2' => null,
            'postal_code' => fake()->postcode(),
            'city_id' => null,
            'district_id' => null,
            'geocoding_status' => GeocodingStatus::Pending,
            'status' => CompanyStatus::Active,
            'activity_id' => null,
        ];
    }
}
