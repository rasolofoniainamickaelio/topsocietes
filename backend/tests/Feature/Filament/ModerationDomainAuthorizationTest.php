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

it('lets moderator access dispute reports', function (): void {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($moderator)->get('/admin/dispute-reports')->assertSuccessful();
});

it('denies content_manager access to dispute reports', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/dispute-reports')->assertForbidden();
});
