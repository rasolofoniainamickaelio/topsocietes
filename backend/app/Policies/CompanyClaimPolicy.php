<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CompanyClaim;
use App\Models\User;

class CompanyClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ClaimsView->value);
    }

    /** Le staff avec `claims.view`, ou l'auteur de la revendication. */
    public function view(User $user, CompanyClaim $claim): bool
    {
        return $user->can(PermissionName::ClaimsView->value) || $claim->user_id === $user->id;
    }

    /** Revendiquer une fiche est une action ouverte à tout utilisateur authentifié. */
    public function create(User $user): bool
    {
        return true;
    }

    public function review(User $user, CompanyClaim $claim): bool
    {
        return $user->can(PermissionName::ClaimsReview->value);
    }
}
