<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

/**
 * Isole le Domain du prestataire de paiement pour le remboursement, même
 * patron que `CheckoutGatewayInterface` (ADR 0001/0004).
 */
interface RefundGatewayInterface
{
    public function refund(string $providerPaymentId): void;
}
