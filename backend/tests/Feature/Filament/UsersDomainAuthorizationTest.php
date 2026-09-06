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

it('lets super_admin access user management', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $this->actingAs($superAdmin)->get('/admin/users')->assertSuccessful();
});

it('denies admin access to user management', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->get('/admin/users')->assertForbidden();
});

it('lets admin and super_admin access job runs, but not moderator', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($admin)->get('/admin/job-runs')->assertSuccessful();
    $this->actingAs($superAdmin)->get('/admin/job-runs')->assertSuccessful();
    $this->actingAs($moderator)->get('/admin/job-runs')->assertForbidden();
});

it('lets admin and super_admin access the activity log and failed jobs, but not moderator', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($admin)->get('/admin/activity-logs')->assertSuccessful();
    $this->actingAs($superAdmin)->get('/admin/activity-logs')->assertSuccessful();
    $this->actingAs($moderator)->get('/admin/activity-logs')->assertForbidden();

    $this->actingAs($admin)->get('/admin/failed-jobs')->assertSuccessful();
    $this->actingAs($superAdmin)->get('/admin/failed-jobs')->assertSuccessful();
    $this->actingAs($moderator)->get('/admin/failed-jobs')->assertForbidden();
});
