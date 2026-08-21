<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * `users.manage` n'est assignée à aucun rôle en base : seul le bypass
 * `Gate::before` de super_admin y donne accès — admin ne gère jamais les
 * comptes/rôles des autres utilisateurs (docs/adr/0002-access-control.md).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersManage->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersManage->value);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersManage->value);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersManage->value);
    }
}
