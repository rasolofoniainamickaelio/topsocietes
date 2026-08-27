<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

it('lets admin access the billing resources', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get('/admin/plans')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/subscriptions')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/payments')->assertSuccessful();
});

it('denies moderator access to the billing resources', function (): void {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($moderator)->get('/admin/plans')->assertForbidden();
    $this->actingAs($moderator)->get('/admin/subscriptions')->assertForbidden();
});
