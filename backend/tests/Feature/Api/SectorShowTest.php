<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;
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

it('exposes its path, is_indexable and activity links', function (): void {
    $country = Country::factory()->create([
        'subdomain' => 'fr',
        'is_active' => true,
        'url_patterns' => ['sector' => '/secteur/{sector}'],
    ]);
    $sector = Sector::factory()->create(['name' => 'Transport et logistique']);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Transport urbain']);
    $sector->activities()->attach($activity->id);

    $response = $this->getJson("/api/v1/fr/sectors/{$sector->slug}");

    $response->assertOk()
        ->assertJsonPath('data.path', "/secteur/{$sector->slug}")
        ->assertJsonPath('data.is_indexable', true)
        ->assertJsonPath('data.links.activities.0.label', 'Transport urbain');
});

it('returns 404 for an unknown sector', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/sectors/does-not-exist');

    $response->assertNotFound();
});
