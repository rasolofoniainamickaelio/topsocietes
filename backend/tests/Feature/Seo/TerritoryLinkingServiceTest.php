<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncActivityCityPageRouteAction;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;
use App\Domain\Taxonomy\Models\Sector;

it('links to an activity-city page only once its route is synced', function (): void {
    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create(['name' => 'Lyon']);
    $activity = Activity::factory()->create(['public_label' => 'Plombier']);
    Company::factory()->for($country)->for($city, 'city')->for($activity)->create();

    $links = app(TerritoryLinkingService::class)->forCity($city, $country);
    expect($links->activities)->toBeEmpty();

    app(SyncActivityCityPageRouteAction::class)->execute($city, $activity, $country);

    $links = app(TerritoryLinkingService::class)->forCity($city, $country);
    expect($links->activities)->toHaveCount(1)
        ->and($links->activities[0]->label)->toBe('Plombier à Lyon')
        ->and($links->activities[0]->path)->toBe("/{$city->slug}/{$activity->slug}");
});

it('always links to the country home page', function (): void {
    $country = Country::factory()->create(['name' => 'France']);
    $city = City::factory()->for($country)->create();

    $links = app(TerritoryLinkingService::class)->forCity($city, $country);

    expect($links->country->label)->toBe('France')
        ->and($links->country->path)->toBe('/');
});

it('links a city up to its department and region once their routes are synced', function (): void {
    $country = Country::factory()->create(['name' => 'France']);
    $region = AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Auvergne-Rhône-Alpes']);
    $department = AdminDivision::factory()->for($country)->create([
        'level' => 2,
        'parent_id' => $region->id,
        'name' => 'Rhône',
    ]);
    $city = City::factory()->for($country)->create(['admin_division_id' => $department->id]);
    $city->load('adminDivision.parent');

    $links = app(TerritoryLinkingService::class)->forCity($city, $country);

    expect($links->department?->label)->toBe('Rhône')
        ->and($links->department?->path)->not->toBeNull()
        ->and($links->region?->label)->toBe('Auvergne-Rhône-Alpes')
        ->and($links->region?->path)->not->toBeNull();
});

it('links a department up to its region and down to its cities already synced', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Auvergne-Rhône-Alpes']);
    $department = AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id, 'name' => 'Rhône']);
    $city = City::factory()->for($country)->create(['admin_division_id' => $department->id, 'name' => 'Lyon']);

    $links = app(TerritoryLinkingService::class)->forAdminDivision($department, $country);

    expect($links->parent->label)->toBe('Auvergne-Rhône-Alpes')
        ->and($links->children)->toHaveCount(1)
        ->and($links->children[0]->label)->toBe('Lyon');
});

it('links a region down to its departments, and up to the country home when it has no parent', function (): void {
    $country = Country::factory()->create(['name' => 'France']);
    $region = AdminDivision::factory()->for($country)->create(['level' => 1]);
    AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id]);

    $links = app(TerritoryLinkingService::class)->forAdminDivision($region, $country);

    expect($links->parent->path)->toBe('/')
        ->and($links->children)->toHaveCount(1);
});

it('links an activity up to its parent activity only once its route is synced', function (): void {
    $country = Country::factory()->create();
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $parent = Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Transport']);
    $child = Activity::factory()->for($nomenclature, 'nomenclature')->create(['parent_id' => $parent->id]);

    $links = app(TerritoryLinkingService::class)->forActivity($child, $country);

    expect($links->parent)->not->toBeNull()
        ->and($links->parent->label)->toBe('Transport');
});

it('has no parent link when the activity has no parent activity', function (): void {
    $country = Country::factory()->create();
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();

    $links = app(TerritoryLinkingService::class)->forActivity($activity, $country);

    expect($links->parent)->toBeNull();
});

it('links to a sector using the route of the current country, never a different country\'s route for the same sector', function (): void {
    // Un secteur est transversal : il a une route par pays actif. Sans le
    // filtre par `country_id` dans `routableLink` (bug corrigé Phase
    // 13.c), un lien vers ce secteur depuis la page d'une activité
    // française pourrait pointer vers le chemin d'un autre pays.
    $countryA = Country::factory()->create(['url_patterns' => ['sector' => '/secteur/{sector}']]);
    $countryB = Country::factory()->create(['url_patterns' => ['sector' => '/sector-b/{sector}']]);
    $sector = Sector::factory()->create(); // synchronise déjà ses deux routes via l'Observer

    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $countryA->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create();
    $sector->activities()->attach($activity->id);

    $routeA = PageRoute::where('country_id', $countryA->id)->where('entity_type', $sector->getMorphClass())->where('entity_id', $sector->id)->firstOrFail();

    $links = app(TerritoryLinkingService::class)->forActivity($activity, $countryA);

    expect($links->sectors)->toHaveCount(1)
        ->and($links->sectors[0]->path)->toBe($routeA->path)
        ->and($links->sectors[0]->path)->not->toContain('sector-b');
});

it('links a sector to its activities', function (): void {
    $country = Country::factory()->create();
    $sector = Sector::factory()->create(['name' => 'Transport et logistique']);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    $activity = Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Plombier']);
    $sector->activities()->attach($activity->id);

    $links = app(TerritoryLinkingService::class)->forSector($sector, $country);

    expect($links->activities)->toHaveCount(1)
        ->and($links->activities[0]->label)->toBe('Plombier');
});

it('links a country to its top-level regions and publishable activities', function (): void {
    $country = Country::factory()->create();
    $region = AdminDivision::factory()->for($country)->create(['level' => 1, 'name' => 'Bretagne']);
    AdminDivision::factory()->for($country)->create(['level' => 2, 'parent_id' => $region->id]);
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    Activity::factory()->for($nomenclature, 'nomenclature')->create(['public_label' => 'Plombier', 'is_publishable' => true]);
    Activity::factory()->for($nomenclature, 'nomenclature')->create(['is_publishable' => false]);

    $links = app(TerritoryLinkingService::class)->forCountry($country);

    expect($links->regions)->toHaveCount(1)
        ->and($links->regions[0]->label)->toBe('Bretagne')
        ->and($links->activities)->toHaveCount(1)
        ->and($links->activities[0]->label)->toBe('Plombier');
});
