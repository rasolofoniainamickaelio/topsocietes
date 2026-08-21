<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Matrice rôle → permissions, voir `docs/adr/0002-access-control.md`.
 *
 * `super_admin` ne reçoit aucune permission ici : il contourne toute
 * vérification via le `Gate::before` de `AppServiceProvider`. `company_owner`
 * ne reçoit aucune permission globale non plus — son accès à sa propre fiche
 * vient exclusivement du lien `CompanyClaim` approuvée, vérifié dans
 * `CompanyPolicy`, jamais d'une permission spatie.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionName::cases() as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        }

        $roles = [];
        foreach (RoleName::cases() as $role) {
            $roles[$role->value] = Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }

        $roles[RoleName::Admin->value]->syncPermissions(array_map(
            fn (PermissionName $permission): string => $permission->value,
            array_filter(
                PermissionName::cases(),
                fn (PermissionName $permission): bool => $permission !== PermissionName::UsersManage,
            ),
        ));

        $roles[RoleName::Moderator->value]->syncPermissions([
            PermissionName::CompaniesView->value,
            PermissionName::ContactsViewMasked->value,
            PermissionName::ClaimsView->value,
            PermissionName::ClaimsReview->value,
            PermissionName::DisputesView->value,
            PermissionName::DisputesReview->value,
        ]);

        $roles[RoleName::ContentManager->value]->syncPermissions([
            PermissionName::ContentManage->value,
            PermissionName::SourcesManage->value,
            PermissionName::AiPipelineManage->value,
        ]);
    }
}
