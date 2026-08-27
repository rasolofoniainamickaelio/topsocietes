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

it('lets admin access the geo resources', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get('/admin/countries')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/admin-divisions')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/cities')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/districts')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/point-of-interests')->assertSuccessful();
});

it('denies content_manager access to the geo resources', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/countries')->assertForbidden();
    $this->actingAs($contentManager)->get('/admin/cities')->assertForbidden();
});
