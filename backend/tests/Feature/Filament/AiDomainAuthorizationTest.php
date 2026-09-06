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

it('lets content_manager access the AI resources', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/ai-prompts')->assertSuccessful();
    $this->actingAs($contentManager)->get('/admin/ai-generation-jobs')->assertSuccessful();
});

it('denies moderator access to the AI resources', function (): void {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $this->actingAs($moderator)->get('/admin/ai-prompts')->assertForbidden();
});

it('lets content_manager open the launch-generation page', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $this->actingAs($contentManager)->get('/admin/ai-generation-jobs/create')->assertSuccessful();
});
