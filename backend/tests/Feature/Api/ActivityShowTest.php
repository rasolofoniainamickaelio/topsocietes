<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;

it('returns an activity with its country-specific content preferred over the generic one', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Transport urbain']);

    ActivityContent::factory()->create([
        'activity_id' => $activity->id,
        'country_id' => null,
        'section' => 'understanding_sector',
        'status' => ContentStatus::Published,
        'body' => 'Contenu générique',
    ]);
    ActivityContent::factory()->create([
        'activity_id' => $activity->id,
        'country_id' => $country->id,
        'section' => 'understanding_sector',
        'status' => ContentStatus::Published,
        'body' => 'Contenu France',
    ]);

    $response = $this->getJson("/api/v1/fr/activities/{$activity->slug}");

    $response->assertOk()
        ->assertJsonPath('data.label', 'Transport urbain')
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.data.body', 'Contenu France');
});

it('returns 404 for an unknown activity', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/activities/does-not-exist');

    $response->assertNotFound();
});
