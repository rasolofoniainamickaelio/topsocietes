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

it('denies admin from managing other users', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $target = User::factory()->create();

    expect($admin->can('update', $target))->toBeFalse();
});

it('lets super_admin manage users via the Gate::before bypass', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $target = User::factory()->create();

    expect($superAdmin->can('update', $target))->toBeTrue();
});
