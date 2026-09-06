<?php

declare(strict_types=1);

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Contracts\RefundGatewayInterface;
use Stripe\StripeClient;

class StripeRefundGateway implements RefundGatewayInterface
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function refund(string $providerPaymentId): void
    {
        $this->stripe->refunds->create([
            'payment_intent' => $providerPaymentId,
        ]);
    }
}
