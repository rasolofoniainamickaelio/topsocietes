<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncActivityCityPageRouteAction;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;
use App\Domain\Taxonomy\Models\Sector;

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

it('exposes its path, is_indexable and territory links', function (): void {
    $country = Country::factory()->create([
        'subdomain' => 'fr',
        'is_active' => true,
        'url_patterns' => [
            'activity' => '/activite/{activity}',
            'activity_city' => '/{city}/{activity}',
            'sector' => '/secteur/{sector}',
        ],
    ]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Transport urbain']);
    // `SectorObserver` synchronise déjà sa route pour ce pays à la création
    // (Phase 13.c) — rien à simuler manuellement.
    $sector = Sector::factory()->create(['name' => 'Transport et logistique']);
    $activity->sectors()->attach($sector->id);
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    Company::factory()->for($country)->for($city, 'city')->for($activity)->create();
    app(SyncActivityCityPageRouteAction::class)->execute($city, $activity, $country);

    $response = $this->getJson("/api/v1/fr/activities/{$activity->slug}");

    $response->assertOk()
        ->assertJsonPath('data.path', "/activite/{$activity->slug}")
        ->assertJsonPath('data.is_indexable', true)
        ->assertJsonPath('data.links.sectors.0.label', 'Transport et logistique')
        ->assertJsonPath('data.links.cities.0.label', 'Transport urbain à Lyon');
});

it('omits a city link when its activity-city route is not yet synced', function (): void {
    $country = Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();
    $city = City::factory()->for($country)->create();
    Company::factory()->for($country)->for($city, 'city')->for($activity)->create();
    // Pas de synchronisation ici : la route activité×ville n'existe pas.

    $response = $this->getJson("/api/v1/fr/activities/{$activity->slug}");

    $response->assertOk()->assertJsonCount(0, 'data.links.cities');
});

it('returns 404 for an unknown activity', function (): void {
    Country::factory()->create(['subdomain' => 'fr', 'is_active' => true]);

    $response = $this->getJson('/api/v1/fr/activities/does-not-exist');

    $response->assertNotFound();
});
