<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\ContentSection;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;

it('lists admin divisions filtered by level', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Auvergne-Rhône-Alpes']);
    AdminDivision::factory()->for($country)->create(['level' => 2, 'name' => 'Rhône']);

    $response = $this->getJson('/api/v1/fr/admin-divisions?level=1');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Auvergne-Rhône-Alpes');
});

it('shows a division with its children and falls back to a parent content block', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $region = AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Auvergne-Rhône-Alpes']);
    $department = AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id, 'name' => 'Rhône', 'slug' => 'rhone']);

    ContentSection::factory()->create(['key' => 'stats', 'scope' => ContentSectionScope::AdminDivision, 'is_enabled' => true]);
    AdminDivisionContent::factory()->for($region, 'adminDivision')->create([
        'section' => 'stats',
        'status' => ContentStatus::Published,
    ]);

    $response = $this->getJson('/api/v1/fr/admin-divisions/rhone');

    $response->assertOk()
        ->assertJsonPath('data.name', 'Rhône')
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.type', 'stats');
});

it('returns 404 for an unknown division', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/admin-divisions/does-not-exist');

    $response->assertNotFound();
});
