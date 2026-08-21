<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'country_id' => null,
            'price_cents' => fake()->numberBetween(990, 9990),
            'currency' => 'EUR',
            'billing_period' => BillingPeriod::Month,
            'features' => [],
            'is_active' => true,
        ];
    }
}
