<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CompanyContact;
use App\Models\User;

class CompanyContactPolicy
{
    public function manage(User $user, CompanyContact $contact): bool
    {
        return $user->can(PermissionName::ContactsManage->value);
    }

    /**
     * Voir les coordonnées démasquées hors du flux d'abonnement (support,
     * modération). Aucun bypass pour le propriétaire de la fiche : ses
     * propres coordonnées suivent la même règle que le public — décision
     * actée dans docs/adr/0002-access-control.md.
     */
    public function viewMasked(User $user, CompanyContact $contact): bool
    {
        return $user->can(PermissionName::ContactsViewMasked->value);
    }
}
