<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Enums\PaymentProvider;
use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'provider' => PaymentProvider::Stripe,
            'provider_payment_id' => 'pi_'.fake()->unique()->bothify('##########'),
            'amount_cents' => fake()->numberBetween(990, 9990),
            'currency' => 'EUR',
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'payload' => [],
        ];
    }
}
