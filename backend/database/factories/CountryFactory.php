<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'iso_alpha2' => strtoupper(fake()->lexify('??')),
            'name' => fake()->country(),
            'subdomain' => fake()->unique()->domainWord().'.topsocietes.com',
            'default_locale' => 'fr_FR',
            'currency' => 'EUR',
            'timezone' => 'Europe/Paris',
            'is_active' => true,
            'admin_level_labels' => ['1' => 'Région', '2' => 'Département'],
            'identifier_config' => ['label' => 'SIREN', 'pattern' => '^\\d{9}$'],
            'activity_nomenclature_code' => 'NAF2008',
            'url_patterns' => [],
            'source_config' => [],
            'settings' => [],
        ];
    }
}
