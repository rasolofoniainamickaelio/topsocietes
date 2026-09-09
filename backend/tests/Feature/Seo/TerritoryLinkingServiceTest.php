<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncActivityCityPageRouteAction;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Domain\Taxonomy\Models\Activity;

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
