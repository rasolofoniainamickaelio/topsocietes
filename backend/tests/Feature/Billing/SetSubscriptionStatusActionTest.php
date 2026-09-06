<?php

declare(strict_types=1);

use App\Domain\Billing\Actions\SetSubscriptionStatusAction;
use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\CompanyContact;
use App\Models\User;

it('force-unmasks contacts when an admin activates a subscription', function (): void {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Pending]);
    $contact = CompanyContact::factory()->for($subscription->company)->create(['visibility' => ContactVisibility::Hidden]);
    $admin = User::factory()->create();

    app(SetSubscriptionStatusAction::class)->execute($subscription, SubscriptionStatus::Active, $admin);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($contact->fresh()->visibility)->toBe(ContactVisibility::ForcedVisible);

    $event = ContactVisibilityEvent::query()->where('contact_id', $contact->id)->firstOrFail();
    expect($event->action)->toBe(ContactVisibilityAction::AdminOverride)
        ->and($event->user_id)->toBe($admin->id);
});

it('force-hides contacts when an admin deactivates an active subscription', function (): void {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);
    $contact = CompanyContact::factory()->for($subscription->company)->create(['visibility' => ContactVisibility::Visible]);

    app(SetSubscriptionStatusAction::class)->execute($subscription, SubscriptionStatus::Suspended);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Suspended)
        ->and($contact->fresh()->visibility)->toBe(ContactVisibility::ForcedHidden);
});

it('changes nothing about contact visibility for a transition between two non-active statuses', function (): void {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::PastDue]);
    $contact = CompanyContact::factory()->for($subscription->company)->create(['visibility' => ContactVisibility::Hidden]);

    app(SetSubscriptionStatusAction::class)->execute($subscription, SubscriptionStatus::Suspended);

    expect($contact->fresh()->visibility)->toBe(ContactVisibility::Hidden);
    expect(ContactVisibilityEvent::query()->where('contact_id', $contact->id)->exists())->toBeFalse();
});
