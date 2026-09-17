<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Actions\RecalculateTerritoryCompaniesCountAction;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;

it('recalculates the companies count for a city', function (): void {
    $city = City::factory()->create(['companies_count' => 0]);
    Company::factory()->for($city, 'city')->count(3)->create();
    Company::factory()->create(); // rattachée à une autre ville, ne doit pas compter

    app(RecalculateTerritoryCompaniesCountAction::class)->execute(PageType::City, $city->id);

    expect($city->refresh()->companies_count)->toBe(3);
    expect($city->counts_updated_at)->not->toBeNull();
});

it('recalculates the companies count for a district', function (): void {
    $district = District::factory()->create(['companies_count' => 0]);
    Company::factory()->for($district, 'district')->count(2)->create();

    app(RecalculateTerritoryCompaniesCountAction::class)->execute(PageType::District, $district->id);

    expect($district->refresh()->companies_count)->toBe(2);
});

it('recalculates the companies count for an admin division', function (): void {
    $division = AdminDivision::factory()->create();
    Company::factory()->for($division, 'adminDivision')->count(4)->create();

    app(RecalculateTerritoryCompaniesCountAction::class)->execute(PageType::AdminDivision, $division->id);

    expect($division->refresh()->companies_count)->toBe(4);
});

it('recalculates the companies count for an activity', function (): void {
    $activity = Activity::factory()->create(['companies_count' => 0]);
    Company::factory()->for($activity)->count(5)->create();

    app(RecalculateTerritoryCompaniesCountAction::class)->execute(PageType::Activity, $activity->id);

    expect($activity->refresh()->companies_count)->toBe(5);
});

it('recalculates the companies count for a sector by aggregating through its activities', function (): void {
    $country = Country::factory()->create();
    $sector = Sector::factory()->create(['companies_count' => 0]);
    $activityA = Activity::factory()->create();
    $activityB = Activity::factory()->create();
    $sector->activities()->attach([$activityA->id, $activityB->id]);

    Company::factory()->for($country)->for($activityA)->count(2)->create();
    Company::factory()->for($country)->for($activityB)->count(3)->create();
    Company::factory()->for($country)->create(); // activité hors secteur

    app(RecalculateTerritoryCompaniesCountAction::class)->execute(PageType::Sector, $sector->id);

    expect($sector->refresh()->companies_count)->toBe(5);
});

it('does nothing when the entity no longer exists', function (): void {
    app(RecalculateTerritoryCompaniesCountAction::class)->execute(PageType::City, 999_999);
})->throwsNoExceptions();
