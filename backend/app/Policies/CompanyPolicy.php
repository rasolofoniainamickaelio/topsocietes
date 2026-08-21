<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CompanyClaimStatus;
use App\Enums\PermissionName;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::CompaniesView->value);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can(PermissionName::CompaniesView->value);
    }

    /**
     * Le staff avec `companies.manage`, ou le représentant d'une entreprise
     * dont la revendication a été approuvée — voir
     * docs/adr/0002-access-control.md.
     */
    public function update(User $user, Company $company): bool
    {
        if ($user->can(PermissionName::CompaniesManage->value)) {
            return true;
        }

        return $company->claims()
            ->where('user_id', $user->id)
            ->where('status', CompanyClaimStatus::Approved)
            ->exists();
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can(PermissionName::CompaniesManage->value);
    }
}
