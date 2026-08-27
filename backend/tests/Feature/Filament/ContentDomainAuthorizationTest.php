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

it('lets content_manager access the content resources', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/content-sections')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/city-contents')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/district-contents')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/activity-contents')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/city-activity-contents')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/sources')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/source-documents')->assertSuccessful();
});

it('denies moderator access to the content resources', function (): void {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($moderator)->get('/admin/city-contents')->assertForbidden();
});
