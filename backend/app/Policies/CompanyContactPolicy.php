<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Company\Models\CompanyContact;
use App\Enums\PermissionName;
use App\Models\User;

class CompanyContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ContactsManage->value);
    }

    public function view(User $user, CompanyContact $contact): bool
    {
        return $user->can(PermissionName::ContactsManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ContactsManage->value);
    }

    public function update(User $user, CompanyContact $contact): bool
    {
        return $user->can(PermissionName::ContactsManage->value);
    }

    public function delete(User $user, CompanyContact $contact): bool
    {
        return $user->can(PermissionName::ContactsManage->value);
    }

    /** Alias explicite des méthodes standard ci-dessus, pour un appel `$user->can('manage', $contact)` plus lisible hors contexte CRUD. */
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
