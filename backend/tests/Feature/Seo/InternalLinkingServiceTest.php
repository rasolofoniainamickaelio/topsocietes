<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\CityNeighbor;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\InternalLinkingService;
use App\Domain\Taxonomy\Models\Activity;

beforeEach(function (): void {
    $this->country = Country::factory()->create();
    $this->region = AdminDivision::factory()->for($this->country)->create(['level' => 1, 'name' => 'Auvergne-Rhône-Alpes']);
    $this->department = AdminDivision::factory()->for($this->country)->create([
        'level' => 2,
        'parent_id' => $this->region->id,
        'name' => 'Rhône',
    ]);
    $this->city = City::factory()->for($this->country)->create(['name' => 'Lyon', 'admin_division_id' => $this->department->id]);
    $this->activity = Activity::factory()->create(['public_label' => 'Plombier']);

    $this->company = Company::factory()
        ->for($this->country)
        ->for($this->city, 'city')
        ->for($this->activity)
        ->create([
            'legal_name' => 'Plomberie Dupont',
            'admin_division_id' => $this->department->id,
            'content_status' => CompanyContentStatus::Published,
            'is_indexable' => true,
        ]);
});

it('lists other published companies of the same trade in the same city, excluding itself', function (): void {
    $sameTrade = Company::factory()->for($this->country)->for($this->city, 'city')->for($this->activity)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
    ]);
    Company::factory()->for($this->country)->for($this->city, 'city')->for($this->activity)->create([
        'content_status' => CompanyContentStatus::Pending,
    ]);

    $links = app(InternalLinkingService::class)->forCompany($this->company);

    $slugs = collect($links->sameTradeInCity)->map(fn ($link) => $link->params['slug']);
    expect($slugs)->toContain($sameTrade->slug)
        ->and($slugs)->not->toContain($this->company->slug);
});

it('links to a single activity_city page for the current city, not a list of companies', function (): void {
    $links = app(InternalLinkingService::class)->forCompany($this->company);

    expect($links->activityInCity)->not->toBeNull()
        ->and($links->activityInCity->type)->toBe(PageType::ActivityCity)
        ->and($links->activityInCity->params)->toBe(['citySlug' => $this->city->slug, 'activitySlug' => $this->activity->slug]);
});

it('links to the activity_city page of each precomputed neighbor city', function (): void {
    $neighbor = City::factory()->for($this->country)->create(['name' => 'Villeurbanne']);
    CityNeighbor::create(['city_id' => $this->city->id, 'neighbor_city_id' => $neighbor->id, 'distance_m' => 5000, 'rank' => 1]);

    $links = app(InternalLinkingService::class)->forCompany($this->company);

    expect($links->activityInNeighborCities)->toHaveCount(1)
        ->and($links->activityInNeighborCities[0]->params['citySlug'])->toBe($neighbor->slug);
});

it('links to the department and the region through the admin division hierarchy', function (): void {
    $links = app(InternalLinkingService::class)->forCompany($this->company);

    expect($links->department->label)->toBe('Rhône')
        ->and($links->region->label)->toBe('Auvergne-Rhône-Alpes');
});

it('lists sibling activities as related activities, never the activity itself', function (): void {
    $parent = Activity::factory()->create();
    $this->activity->update(['parent_id' => $parent->id]);
    $sibling = Activity::factory()->create(['parent_id' => $parent->id, 'is_publishable' => true, 'public_label' => 'Électricien']);
    Activity::factory()->create(['parent_id' => $parent->id, 'is_publishable' => false]);

    $links = app(InternalLinkingService::class)->forCompany($this->company->fresh());

    $slugs = collect($links->relatedActivities)->map(fn ($link) => $link->params['slug']);
    expect($slugs)->toContain($sibling->slug)
        ->and($slugs)->not->toContain($this->activity->slug);
});

it('omits the company-creation link when no country configures it', function (): void {
    $links = app(InternalLinkingService::class)->forCompany($this->company);

    expect($links->companyCreation)->toBeNull();
});

it('includes the company-creation link when the country configures a path', function (): void {
    $this->country->update(['url_patterns' => ['company_creation' => '/creer-mon-entreprise']]);

    $links = app(InternalLinkingService::class)->forCompany($this->company->fresh());

    expect($links->companyCreation)->not->toBeNull()
        ->and($links->companyCreation->params['path'])->toBe('/creer-mon-entreprise');
});

it('excludes a company explicitly marked non-indexable via a route', function (): void {
    $excluded = Company::factory()->for($this->country)->for($this->city, 'city')->for($this->activity)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
    ]);
    PageRoute::factory()->create([
        'country_id' => $this->country->id,
        'entity_type' => PageType::Company->value,
        'entity_id' => $excluded->id,
        'page_type' => PageType::Company,
        'is_indexable' => false,
    ]);

    $links = app(InternalLinkingService::class)->forCompany($this->company);

    $slugs = collect($links->sameTradeInCity)->map(fn ($link) => $link->params['slug']);
    expect($slugs)->not->toContain($excluded->slug);
});

it('caches the result and forgets it when a linking-relevant field changes', function (): void {
    $service = app(InternalLinkingService::class);
    $first = $service->forCompany($this->company);

    expect(cache()->has(InternalLinkingService::cacheKey($this->company)))->toBeTrue();

    $this->company->update(['legal_name' => 'Nouveau Nom']);

    expect(cache()->has(InternalLinkingService::cacheKey($this->company)))->toBeFalse();
});
