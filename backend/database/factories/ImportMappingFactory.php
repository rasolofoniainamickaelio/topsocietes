<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\ImportMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportMapping>
 */
class ImportMappingFactory extends Factory
{
    protected $model = ImportMapping::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->words(2, true),
            'column_map' => ['siren' => 'national_id', 'denomination' => 'legal_name'],
            'transformers' => [],
            'is_default' => false,
        ];
    }
}
