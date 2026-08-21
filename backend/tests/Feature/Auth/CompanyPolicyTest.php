<?php

declare(strict_types=1);

use App\Enums\CompanyClaimStatus;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\CompanyClaim;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

it('lets admin update any company', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $company = Company::factory()->create();

    expect($admin->can('update', $company))->toBeTrue();
});

it('lets a company_owner update a company they hold an approved claim on', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::CompanyOwner->value);

    $company = Company::factory()->create();
    CompanyClaim::factory()->for($company)->for($owner)->create(['status' => CompanyClaimStatus::Approved]);

    expect($owner->can('update', $company))->toBeTrue();
});

it('denies a company_owner whose claim is still pending', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::CompanyOwner->value);

    $company = Company::factory()->create();
    CompanyClaim::factory()->for($company)->for($owner)->create(['status' => CompanyClaimStatus::Pending]);

    expect($owner->can('update', $company))->toBeFalse();
});

it('denies a company_owner on a company they do not own', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::CompanyOwner->value);

    $ownedCompany = Company::factory()->create();
    CompanyClaim::factory()->for($ownedCompany)->for($owner)->create(['status' => CompanyClaimStatus::Approved]);

    $otherCompany = Company::factory()->create();

    expect($owner->can('update', $otherCompany))->toBeFalse();
});
