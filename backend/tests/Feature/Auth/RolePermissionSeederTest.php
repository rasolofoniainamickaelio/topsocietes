<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

it('assigns no permission directly to super_admin', function (): void {
    $role = Role::findByName(RoleName::SuperAdmin->value);

    expect($role->permissions)->toBeEmpty();
});

it('grants admin every permission except users.manage', function (): void {
    $role = Role::findByName(RoleName::Admin->value);

    $names = $role->permissions->pluck('name')->sort()->values()->all();
    $expected = collect(PermissionName::cases())
        ->reject(fn (PermissionName $permission): bool => $permission === PermissionName::UsersManage)
        ->map(fn (PermissionName $permission): string => $permission->value)
        ->sort()
        ->values()
        ->all();

    expect($names)->toBe($expected);
});

it('scopes moderator to investigation and review abilities', function (): void {
    $role = Role::findByName(RoleName::Moderator->value);

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(collect([
        PermissionName::CompaniesView->value,
        PermissionName::ContactsViewMasked->value,
        PermissionName::ClaimsView->value,
        PermissionName::ClaimsReview->value,
        PermissionName::DisputesView->value,
        PermissionName::DisputesReview->value,
    ])->sort()->values()->all());
});

it('scopes content_manager to the editorial domain', function (): void {
    $role = Role::findByName(RoleName::ContentManager->value);

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(collect([
        PermissionName::ContentManage->value,
        PermissionName::SourcesManage->value,
        PermissionName::AiPipelineManage->value,
    ])->sort()->values()->all());
});

it('assigns no global permission to company_owner', function (): void {
    $role = Role::findByName(RoleName::CompanyOwner->value);

    expect($role->permissions)->toBeEmpty();
});
