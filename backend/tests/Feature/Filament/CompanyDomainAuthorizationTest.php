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

it('lets admin access the company resources', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get('/admin/companies')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/establishments')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/company-contacts')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/company-claims')->assertSuccessful();
});

it('denies content_manager access to the company resources', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/companies')->assertForbidden();
    $this->actingAs($contentManager)->get('/admin/establishments')->assertForbidden();
});

it('lets super_admin access the company resources via the Gate::before bypass', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $this->actingAs($superAdmin)->get('/admin/companies')->assertSuccessful();
});
