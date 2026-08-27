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

it('lets admin access the ads resources', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get('/admin/ad-slots')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/ad-campaigns')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/service-links')->assertSuccessful();
});

it('denies content_manager access to the ads resources', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/ad-slots')->assertForbidden();
});
