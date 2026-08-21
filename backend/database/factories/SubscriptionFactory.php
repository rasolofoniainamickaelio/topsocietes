<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Enums\PaymentProvider;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'provider' => PaymentProvider::Stripe,
            'provider_subscription_id' => 'sub_'.fake()->unique()->bothify('##########'),
            'started_at' => now(),
            'current_period_end' => now()->addMonth(),
            'cancelled_at' => null,
            'auto_renew' => true,
        ];
    }
}
