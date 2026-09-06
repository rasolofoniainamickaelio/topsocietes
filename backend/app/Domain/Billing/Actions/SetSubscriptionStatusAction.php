<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Enums\ContactVisibility;
use App\Models\User;

/**
 * Override manuel du statut d'un abonnement (Phase 07, Phase 21) — force la
 * visibilité des contacts en conséquence via `ForcedVisible`/`ForcedHidden`,
 * jamais `Visible`/`Hidden` : un forçage admin ne doit pas être confondu
 * avec l'état naturel du cycle de vie ni défait par lui.
 */
class SetSubscriptionStatusAction
{
    public function __construct(private readonly SetContactsVisibilityAction $setVisibility) {}

    public function execute(Subscription $subscription, SubscriptionStatus $status, ?User $actor = null): void
    {
        $wasActive = $subscription->status === SubscriptionStatus::Active;

        $subscription->update(['status' => $status]);

        if ($status === SubscriptionStatus::Active && ! $wasActive) {
            $this->setVisibility->execute(
                companyId: $subscription->company_id,
                visibility: ContactVisibility::ForcedVisible,
                action: ContactVisibilityAction::AdminOverride,
                trigger: ContactVisibilityTrigger::Admin,
                subscriptionId: $subscription->id,
                userId: $actor?->id,
                force: true,
            );

            return;
        }

        if ($wasActive && $status !== SubscriptionStatus::Active) {
            $this->setVisibility->execute(
                companyId: $subscription->company_id,
                visibility: ContactVisibility::ForcedHidden,
                action: ContactVisibilityAction::AdminOverride,
                trigger: ContactVisibilityTrigger::Admin,
                subscriptionId: $subscription->id,
                userId: $actor?->id,
                force: true,
            );
        }
    }
}
