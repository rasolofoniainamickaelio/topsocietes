<?php

declare(strict_types=1);

use App\Domain\Billing\Actions\RefundPaymentAction;
use App\Domain\Billing\Contracts\RefundGatewayInterface;
use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Models\Payment;

/**
 * Aucun appel réseau réel vers Stripe : le gateway est remplacé par un faux,
 * même patron que `CheckoutTest` (docs/adr/0004-billing-auth.md).
 */
beforeEach(function (): void {
    $this->refundedPaymentId = null;

    $this->app->bind(RefundGatewayInterface::class, fn (): RefundGatewayInterface => new class($this) implements RefundGatewayInterface
    {
        public function __construct(private object $test) {}

        public function refund(string $providerPaymentId): void
        {
            $this->test->refundedPaymentId = $providerPaymentId;
        }
    });
});

it('refunds a succeeded payment via the gateway and marks it refunded', function (): void {
    $payment = Payment::factory()->create(['status' => PaymentStatus::Succeeded, 'provider_payment_id' => 'pi_123']);

    app(RefundPaymentAction::class)->execute($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($this->refundedPaymentId)->toBe('pi_123');
});

it('refuses to refund a payment that is not succeeded', function (): void {
    $payment = Payment::factory()->create(['status' => PaymentStatus::Failed]);

    app(RefundPaymentAction::class)->execute($payment);
})->throws(RuntimeException::class, 'Seul un paiement réussi peut être remboursé.');

it('refuses to refund a payment with no provider reference', function (): void {
    $payment = Payment::factory()->create(['status' => PaymentStatus::Succeeded, 'provider_payment_id' => null]);

    app(RefundPaymentAction::class)->execute($payment);
})->throws(RuntimeException::class);
