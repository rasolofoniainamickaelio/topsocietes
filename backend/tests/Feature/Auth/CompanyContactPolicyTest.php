<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Company\Models\CompanyContact;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

/**
 * Décision actée (docs/adr/0002-access-control.md) : même le propriétaire
 * d'une fiche ne voit ses coordonnées démasquées que dans les mêmes
 * conditions que le public (abonnement actif) — aucun bypass ici.
 */
it('denies a company_owner from viewing their own masked contact', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::CompanyOwner->value);

    $company = Company::factory()->create();
    CompanyClaim::factory()->for($company)->for($owner)->create(['status' => CompanyClaimStatus::Approved]);
    $contact = CompanyContact::factory()->for($company)->create();

    expect($owner->can('viewMasked', $contact))->toBeFalse();
});

it('lets admin and moderator view masked contacts', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $contact = CompanyContact::factory()->create();

    expect($admin->can('viewMasked', $contact))->toBeTrue()
        ->and($moderator->can('viewMasked', $contact))->toBeTrue();
});
