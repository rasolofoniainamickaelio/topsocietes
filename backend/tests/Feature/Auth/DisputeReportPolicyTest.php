<?php

declare(strict_types=1);

use App\Domain\Moderation\Models\DisputeReport;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

it('lets moderator review a dispute', function (): void {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleName::Moderator->value);

    $dispute = DisputeReport::factory()->create();

    expect($moderator->can('review', $dispute))->toBeTrue();
});

it('denies content_manager from reviewing a dispute', function (): void {
    $contentManager = User::factory()->create();
    $contentManager->assignRole(RoleName::ContentManager->value);

    $dispute = DisputeReport::factory()->create();

    expect($contentManager->can('review', $dispute))->toBeFalse();
});
