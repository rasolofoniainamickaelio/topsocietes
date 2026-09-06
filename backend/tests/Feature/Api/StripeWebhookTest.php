<?php

declare(strict_types=1);

use App\Domain\Billing\Actions\HandleStripeWebhookAction;
use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Enums\PaymentProvider;
use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyContact;
use App\Models\User;

/**
 * `HandleStripeWebhookAction` prend un type d'événement + payload déjà
 * parsés (voir docstring de la classe) : ces tests n'appellent jamais le
 * SDK Stripe ni ne vérifient de signature — c'est le rôle de
 * `StripeWebhookController`, non couvert ici faute de clés Stripe réelles.
 */
it('activates a subscription and unmasks contacts on checkout completed', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $contact = CompanyContact::factory()->for($company)->create(['visibility' => ContactVisibility::Hidden]);

    app(HandleStripeWebhookAction::class)->execute('checkout.session.completed', [
        'subscription' => 'sub_123',
        'payment_intent' => 'pi_123',
        'amount_total' => 1900,
        'currency' => 'eur',
        'metadata' => [
            'company_id' => (string) $company->id,
            'user_id' => (string) $user->id,
            'plan_id' => (string) $plan->id,
        ],
    ]);

    $subscription = Subscription::query()->where('company_id', $company->id)->firstOrFail();
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->provider)->toBe(PaymentProvider::Stripe)
        ->and($subscription->provider_subscription_id)->toBe('sub_123')
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->plan_id)->toBe($plan->id);

    $payment = Payment::query()->where('subscription_id', $subscription->id)->firstOrFail();
    expect($payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->amount_cents)->toBe(1900)
        ->and($payment->currency)->toBe('EUR');

    $event = ContactVisibilityEvent::query()->where('contact_id', $contact->id)->firstOrFail();
    expect($event->action)->toBe(ContactVisibilityAction::Unmasked)
        ->and($event->triggered_by)->toBe(ContactVisibilityTrigger::SubscriptionActivated)
        ->and($event->subscription_id)->toBe($subscription->id);

    expect($contact->fresh()->visibility)->toBe(ContactVisibility::Visible);
});

it('is idempotent when replaying the same checkout completed event', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $payload = [
        'subscription' => 'sub_123',
        'payment_intent' => 'pi_123',
        'amount_total' => 1900,
        'currency' => 'eur',
        'metadata' => [
            'company_id' => (string) $company->id,
            'user_id' => (string) $user->id,
            'plan_id' => (string) $plan->id,
        ],
    ];

    $action = app(HandleStripeWebhookAction::class);
    $action->execute('checkout.session.completed', $payload);
    $action->execute('checkout.session.completed', $payload);

    $subscription = Subscription::query()->where('company_id', $company->id)->firstOrFail();
    expect(Subscription::query()->where('company_id', $company->id)->count())->toBe(1)
        ->and(Payment::query()->where('subscription_id', $subscription->id)->count())->toBe(1);
});

it('cancels a subscription on customer.subscription.deleted', function (): void {
    $subscription = Subscription::factory()->create([
        'provider' => PaymentProvider::Stripe,
        'provider_subscription_id' => 'sub_123',
        'status' => SubscriptionStatus::Active,
    ]);

    app(HandleStripeWebhookAction::class)->execute('customer.subscription.deleted', [
        'id' => 'sub_123',
    ]);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->fresh()->cancelled_at)->not->toBeNull();
});

it('marks a subscription past due on invoice.payment_failed', function (): void {
    $subscription = Subscription::factory()->create([
        'provider' => PaymentProvider::Stripe,
        'provider_subscription_id' => 'sub_123',
        'status' => SubscriptionStatus::Active,
    ]);

    app(HandleStripeWebhookAction::class)->execute('invoice.payment_failed', [
        'subscription' => 'sub_123',
    ]);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue);
});

it('records the renewed period end and recovers from past_due on customer.subscription.updated', function (): void {
    $subscription = Subscription::factory()->create([
        'provider' => PaymentProvider::Stripe,
        'provider_subscription_id' => 'sub_123',
        'status' => SubscriptionStatus::PastDue,
        'current_period_end' => now()->subDay(),
    ]);

    $newPeriodEnd = now()->addMonth()->startOfSecond();

    app(HandleStripeWebhookAction::class)->execute('customer.subscription.updated', [
        'id' => 'sub_123',
        'status' => 'active',
        'current_period_end' => $newPeriodEnd->timestamp,
    ]);

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->current_period_end->equalTo($newPeriodEnd))->toBeTrue();
});

it('ignores customer.subscription.updated for an unknown subscription id', function (): void {
    $countBefore = Subscription::query()->count();

    app(HandleStripeWebhookAction::class)->execute('customer.subscription.updated', [
        'id' => 'sub_unknown',
        'status' => 'active',
        'current_period_end' => now()->timestamp,
    ]);

    expect(Subscription::query()->count())->toBe($countBefore);
});

it('ignores an unknown event type', function (): void {
    $countBefore = Subscription::query()->count();

    app(HandleStripeWebhookAction::class)->execute('some.unknown.event', ['foo' => 'bar']);

    expect(Subscription::query()->count())->toBe($countBefore);
});
