<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityNomenclature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityNomenclature>
 */
class ActivityNomenclatureFactory extends Factory
{
    protected $model = ActivityNomenclature::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('NOMENCL-####')),
            'name' => fake()->words(3, true),
            'country_id' => null,
            'version' => '2008',
        ];
    }
}
