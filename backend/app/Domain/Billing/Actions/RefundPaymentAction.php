<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Contracts\RefundGatewayInterface;
use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Models\Payment;
use RuntimeException;

class RefundPaymentAction
{
    public function __construct(private readonly RefundGatewayInterface $gateway) {}

    public function execute(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Succeeded) {
            throw new RuntimeException('Seul un paiement réussi peut être remboursé.');
        }

        if ($payment->provider_payment_id === null) {
            throw new RuntimeException('Ce paiement n\'a pas de référence prestataire : remboursement impossible.');
        }

        $this->gateway->refund($payment->provider_payment_id);

        $payment->update(['status' => PaymentStatus::Refunded]);
    }
}
