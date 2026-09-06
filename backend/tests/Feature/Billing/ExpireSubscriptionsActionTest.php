<?php

declare(strict_types=1);

use App\Domain\Billing\Actions\ExpireSubscriptionsAction;
use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\CompanyContact;

it('expires a subscription past its period end and re-masks its contacts', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->subDay(),
    ]);
    $contact = CompanyContact::factory()->for($subscription->company)->create([
        'visibility' => ContactVisibility::Visible,
    ]);

    $count = app(ExpireSubscriptionsAction::class)->execute();

    expect($count)->toBe(1)
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
        ->and($contact->fresh()->visibility)->toBe(ContactVisibility::Hidden);

    $event = ContactVisibilityEvent::query()->where('contact_id', $contact->id)->firstOrFail();
    expect($event->action)->toBe(ContactVisibilityAction::Masked)
        ->and($event->triggered_by)->toBe(ContactVisibilityTrigger::SubscriptionExpired);
});

it('leaves an active subscription with a future period end untouched', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addWeek(),
    ]);

    $count = app(ExpireSubscriptionsAction::class)->execute();

    expect($count)->toBe(0)
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('never touches a subscription with no known period end', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_end' => null,
    ]);

    $count = app(ExpireSubscriptionsAction::class)->execute();

    expect($count)->toBe(0)
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('does not re-mask a contact that was force-unmasked by an admin', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->subDay(),
    ]);
    $contact = CompanyContact::factory()->for($subscription->company)->create([
        'visibility' => ContactVisibility::ForcedVisible,
    ]);

    app(ExpireSubscriptionsAction::class)->execute();

    expect($contact->fresh()->visibility)->toBe(ContactVisibility::ForcedVisible);
});
