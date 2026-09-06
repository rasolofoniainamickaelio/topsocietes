<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\ContactVisibility;

/**
 * Fait tourner le dernier maillon du cycle Phase 07 : visible → expiration
 * → masqué. Ne touche jamais un abonnement dont `current_period_end` est
 * inconnu (pas encore reçu du webhook) — mieux vaut un abonnement qui
 * n'expire pas encore que d'en démasquer un par erreur.
 */
class ExpireSubscriptionsAction
{
    public function __construct(private readonly SetContactsVisibilityAction $setVisibility) {}

    public function execute(): int
    {
        $count = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->cursor()
            ->each(function (Subscription $subscription) use (&$count): void {
                $subscription->update(['status' => SubscriptionStatus::Expired]);

                $this->setVisibility->execute(
                    companyId: $subscription->company_id,
                    visibility: ContactVisibility::Hidden,
                    action: ContactVisibilityAction::Masked,
                    trigger: ContactVisibilityTrigger::SubscriptionExpired,
                    subscriptionId: $subscription->id,
                );

                $count++;
            });

        return $count;
    }
}
