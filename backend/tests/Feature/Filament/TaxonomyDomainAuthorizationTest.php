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

it('lets admin access the taxonomy resources', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get('/admin/activity-nomenclatures')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/activities')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/sectors')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/activity-mappings')->assertSuccessful();
});

it('denies moderator access to the taxonomy resources', function (): void {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($moderator)->get('/admin/activities')->assertForbidden();
});
