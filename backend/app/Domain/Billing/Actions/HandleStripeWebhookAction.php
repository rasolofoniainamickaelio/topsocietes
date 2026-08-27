<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Enums\PaymentProvider;
use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Models\Company;

/**
 * Prend un type d'événement + payload déjà parsés (pas de dépendance au
 * SDK Stripe ici) : la vérification de signature reste confinée au
 * contrôleur, cette Action est donc testable sans HTTP ni réseau.
 */
class HandleStripeWebhookAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(string $eventType, array $data): void
    {
        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($data),
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

        $company = Company::query()->find($companyId);

        if ($company === null) {
            return;
        }

        foreach ($company->contacts as $contact) {
            ContactVisibilityEvent::query()->create([
                'company_id' => $companyId,
                'contact_id' => $contact->id,
                'action' => ContactVisibilityAction::Unmasked,
                'triggered_by' => ContactVisibilityTrigger::SubscriptionActivated,
                'subscription_id' => $subscription->id,
                'user_id' => $userId,
            ]);
        }
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
