<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\CompanyContact;

/**
 * Seul endroit qui bascule réellement `CompanyContact.visibility` — jamais
 * en écrivant la colonne directement ailleurs — et journalise chaque
 * bascule. Réutilisée par le webhook Stripe, l'expiration planifiée et le
 * changement de statut manuel en back-office pour ne jamais dupliquer cette
 * logique (CLAUDE.md §6.3).
 */
class SetContactsVisibilityAction
{
    /**
     * @param  bool  $force  `true` uniquement pour un override manuel en
     *                       back-office (visibilité `ForcedVisible`/
     *                       `ForcedHidden`) : un cycle de vie automatique
     *                       (webhook, expiration planifiée) ne doit jamais
     *                       défaire silencieusement un forçage admin — seul
     *                       un nouvel override explicite le peut.
     */
    public function execute(
        int $companyId,
        ContactVisibility $visibility,
        ContactVisibilityAction $action,
        ContactVisibilityTrigger $trigger,
        ?int $subscriptionId = null,
        ?int $userId = null,
        bool $force = false,
    ): void {
        $query = CompanyContact::query()->where('company_id', $companyId);

        if (! $force) {
            $query->whereIn('visibility', [ContactVisibility::Hidden, ContactVisibility::Visible]);
        }

        $contacts = $query->get();

        if ($contacts->isEmpty()) {
            return;
        }

        CompanyContact::query()->whereIn('id', $contacts->pluck('id'))->update(['visibility' => $visibility]);

        foreach ($contacts as $contact) {
            ContactVisibilityEvent::query()->create([
                'company_id' => $companyId,
                'contact_id' => $contact->id,
                'action' => $action,
                'triggered_by' => $trigger,
                'subscription_id' => $subscriptionId,
                'user_id' => $userId,
            ]);
        }
    }
}
