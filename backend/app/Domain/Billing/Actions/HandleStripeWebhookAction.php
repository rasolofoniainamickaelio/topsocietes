<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Enums\PaymentProvider;
use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\ContactVisibility;
use Carbon\Carbon;

/**
 * Prend un type d'événement + payload déjà parsés (pas de dépendance au
 * SDK Stripe ici) : la vérification de signature reste confinée au
 * contrôleur, cette Action est donc testable sans HTTP ni réseau.
 */
class HandleStripeWebhookAction
{
    public function __construct(private readonly SetContactsVisibilityAction $setVisibility) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(string $eventType, array $data): void
    {
        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($data),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($data),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($data),
            'invoice.payment_failed' => $this->handlePaymentFailed($data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleCheckoutCompleted(array $data): void
    {
        $metadata = $data['metadata'] ?? [];
        $companyId = (int) ($metadata['company_id'] ?? 0);
        $userId = (int) ($metadata['user_id'] ?? 0);
        $planId = (int) ($metadata['plan_id'] ?? 0);
        $providerSubscriptionId = $data['subscription'] ?? null;

        if ($companyId === 0 || $planId === 0) {
            return;
        }

        $subscription = Subscription::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'provider' => PaymentProvider::Stripe,
                'provider_subscription_id' => $providerSubscriptionId,
            ],
            [
                'user_id' => $userId,
                'plan_id' => $planId,
                'status' => SubscriptionStatus::Active,
                'started_at' => now(),
                'auto_renew' => true,
            ],
        );

        Payment::query()->create([
            'subscription_id' => $subscription->id,
            'provider' => PaymentProvider::Stripe,
            'provider_payment_id' => $data['payment_intent'] ?? null,
            'amount_cents' => (int) ($data['amount_total'] ?? 0),
            'currency' => strtoupper((string) ($data['currency'] ?? 'eur')),
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'payload' => $data,
        ]);

        // C'est ici, et nulle part ailleurs à l'activation, que les
        // coordonnées doivent réellement passer visibles (Phase 07, "cycle
        // complet ... paiement → visible").
        $this->setVisibility->execute(
            companyId: $companyId,
            visibility: ContactVisibility::Visible,
            action: ContactVisibilityAction::Unmasked,
            trigger: ContactVisibilityTrigger::SubscriptionActivated,
            subscriptionId: $subscription->id,
            userId: $userId,
        );
    }

    /**
     * Stripe envoie cet événement à la création ET à chaque renouvellement :
     * c'est la source fiable de `current_period_end` (jamais disponible sur
     * `checkout.session.completed`), et l'occasion de sortir une
     * souscription de `PastDue` une fois le paiement de reprise accepté.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionUpdated(array $data): void
    {
        $subscription = Subscription::query()
            ->where('provider', PaymentProvider::Stripe)
            ->where('provider_subscription_id', $data['id'] ?? null)
            ->first();

        if ($subscription === null) {
            return;
        }

        $periodEnd = $data['current_period_end'] ?? null;
        $stripeStatus = $data['status'] ?? null;

        $subscription->update([
            'current_period_end' => $periodEnd !== null ? Carbon::createFromTimestamp((int) $periodEnd) : $subscription->current_period_end,
            'status' => $stripeStatus === 'active' && $subscription->status === SubscriptionStatus::PastDue
                ? SubscriptionStatus::Active
                : $subscription->status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionDeleted(array $data): void
    {
        Subscription::query()
            ->where('provider', PaymentProvider::Stripe)
            ->where('provider_subscription_id', $data['id'] ?? null)
            ->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handlePaymentFailed(array $data): void
    {
        Subscription::query()
            ->where('provider', PaymentProvider::Stripe)
            ->where('provider_subscription_id', $data['subscription'] ?? null)
            ->update([
                'status' => SubscriptionStatus::PastDue,
            ]);
    }
}
