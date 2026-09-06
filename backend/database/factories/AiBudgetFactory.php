<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Ai\Models\AiBudget;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiBudget>
 */
class AiBudgetFactory extends Factory
{
    protected $model = AiBudget::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'period' => now()->format('Y-m'),
            'max_cost_cents' => 10_000,
            'spent_cents' => 0,
        ];
    }
}
