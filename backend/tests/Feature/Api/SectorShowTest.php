<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Sector;

it('returns a sector with its published content blocks', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $sector = Sector::factory()->create(['name' => 'Transport et logistique']);

    ActivityContent::factory()->forSector()->create([
        'sector_id' => $sector->id,
        'country_id' => null,
        'section' => 'jobs',
        'status' => ContentStatus::Published,
    ]);

    $response = $this->getJson("/api/v1/fr/sectors/{$sector->slug}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'Transport et logistique')
        ->assertJsonCount(1, 'data.blocks');
});

it('returns 404 for an unknown sector', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/sectors/does-not-exist');

    $response->assertNotFound();
});
