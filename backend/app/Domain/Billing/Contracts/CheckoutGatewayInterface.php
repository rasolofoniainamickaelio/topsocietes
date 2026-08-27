<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

use App\Domain\Billing\Models\Plan;
use App\Domain\Company\Models\Company;
use App\Models\User;

/**
 * Isole le Domain du prestataire de paiement (même patron que
 * `SearchEngineInterface`, ADR 0001) — l'Action qui l'appelle ne sait rien
 * de Stripe, seulement qu'elle obtient une URL de paiement à rediriger.
 */
interface CheckoutGatewayInterface
{
    public function createSubscriptionCheckoutSession(
        User $user,
        Company $company,
        Plan $plan,
        string $successUrl,
        string $cancelUrl,
    ): string;
}
