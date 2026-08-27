<?php

declare(strict_types=1);

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Contracts\CheckoutGatewayInterface;
use App\Domain\Billing\Models\Plan;
use App\Domain\Company\Models\Company;
use App\Models\User;
use Stripe\StripeClient;

/**
 * SDK `stripe-php` direct (tiré par `laravel/cashier`, jamais son trait
 * `Billable` — voir docs/adr/0004-billing-auth.md). `provider_subscription_id`
 * de notre table `subscriptions` est rempli plus tard, depuis le webhook,
 * pas ici : cette classe ne fait que créer la session de paiement.
 */
class StripeCheckoutGateway implements CheckoutGatewayInterface
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createSubscriptionCheckoutSession(
        User $user,
        Company $company,
        Plan $plan,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $plan->currency,
                    'unit_amount' => $plan->price_cents,
                    'product_data' => [
                        'name' => $plan->name,
                    ],
                    'recurring' => [
                        // `BillingPeriod` est déjà backée par 'month'/'year',
                        // qui sont aussi les valeurs Stripe — aucune table de
                        // correspondance nécessaire.
                        'interval' => $plan->billing_period->value,
                    ],
                ],
            ]],
            'metadata' => [
                'company_id' => (string) $company->id,
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
            ],
        ]);

        return (string) $session->url;
    }
}
