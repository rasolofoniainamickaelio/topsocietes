<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Search\Contracts\SearchEngineInterface;
use App\Domain\Search\Data\SearchCompaniesData;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;

beforeEach(function (): void {
    $this->country = Country::factory()->create();
    $this->engine = app(SearchEngineInterface::class);
});

it('finds a company by exact name', function (): void {
    Company::factory()->for($this->country)->create(['legal_name' => 'Keolis Lyon']);
    Company::factory()->for($this->country)->create(['legal_name' => 'Autre Société']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(term: 'Keolis Lyon'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['Keolis Lyon']);
});

it('tolerates a typo via the trigram fallback', function (): void {
    Company::factory()->for($this->country)->create(['legal_name' => 'Keolis Lyon']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(term: 'Keolsi Lyon'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['Keolis Lyon']);
});

it('treats a purely numeric term as a SIREN prefix search, not fuzzy text', function (): void {
    Company::factory()->for($this->country)->create(['national_id' => '552032534', 'legal_name' => 'Keolis Lyon']);
    Company::factory()->for($this->country)->create(['national_id' => '999999999', 'legal_name' => 'Autre Société']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(term: '55203'));

    expect(collect($results->items())->pluck('national_id')->all())->toBe(['552032534']);
});

it('filters by city slug', function (): void {
    $lyon = City::factory()->for($this->country)->create(['slug' => 'lyon']);
    $paris = City::factory()->for($this->country)->create(['slug' => 'paris']);
    Company::factory()->for($this->country)->create(['city_id' => $lyon->id, 'legal_name' => 'A']);
    Company::factory()->for($this->country)->create(['city_id' => $paris->id, 'legal_name' => 'B']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(city: 'lyon'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['A']);
});

it('filters by postal code', function (): void {
    $lyon = City::factory()->for($this->country)->create(['postal_codes' => ['69001', '69002']]);
    $paris = City::factory()->for($this->country)->create(['postal_codes' => ['75001']]);
    Company::factory()->for($this->country)->create(['city_id' => $lyon->id, 'legal_name' => 'A']);
    Company::factory()->for($this->country)->create(['city_id' => $paris->id, 'legal_name' => 'B']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(postal_code: '69001'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['A']);
});

it('filters by activity slug', function (): void {
    $activity = Activity::factory()->create(['slug' => 'transport']);
    Company::factory()->for($this->country)->create(['activity_id' => $activity->id, 'legal_name' => 'A']);
    Company::factory()->for($this->country)->create(['legal_name' => 'B']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(activity: 'transport'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['A']);
});

it('filters by sector slug through the activity relation', function (): void {
    $sector = Sector::factory()->create(['slug' => 'transport-logistique']);
    $activity = Activity::factory()->create();
    $activity->sectors()->attach($sector);
    Company::factory()->for($this->country)->create(['activity_id' => $activity->id, 'legal_name' => 'A']);
    Company::factory()->for($this->country)->create(['legal_name' => 'B']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(sector: 'transport-logistique'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['A']);
});

it('filters by admin division, including its direct children', function (): void {
    $region = AdminDivision::factory()->for($this->country)->create(['slug' => 'auvergne-rhone-alpes', 'level' => 1]);
    $department = AdminDivision::factory()->for($this->country)->create(['parent_id' => $region->id, 'level' => 2]);
    Company::factory()->for($this->country)->create(['admin_division_id' => $department->id, 'legal_name' => 'A']);
    Company::factory()->for($this->country)->create(['legal_name' => 'B']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(admin_division: 'auvergne-rhone-alpes'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['A']);
});

it('returns nothing for an unknown admin division slug, never the whole table', function (): void {
    Company::factory()->for($this->country)->create(['legal_name' => 'A']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(admin_division: 'does-not-exist'));

    expect($results->items())->toBeEmpty();
});

it('combines multiple filters', function (): void {
    $activity = Activity::factory()->create(['slug' => 'transport']);
    $city = City::factory()->for($this->country)->create(['slug' => 'lyon']);
    Company::factory()->for($this->country)->create(['activity_id' => $activity->id, 'city_id' => $city->id, 'legal_name' => 'A']);
    Company::factory()->for($this->country)->create(['activity_id' => $activity->id, 'legal_name' => 'B']);
    Company::factory()->for($this->country)->create(['city_id' => $city->id, 'legal_name' => 'C']);

    $results = $this->engine->searchCompanies($this->country, new SearchCompaniesData(city: 'lyon', activity: 'transport'));

    expect(collect($results->items())->pluck('legal_name')->all())->toBe(['A']);
});

it('paginates by cursor across two pages without duplicates or gaps, browsing without a term', function (): void {
    Company::factory()->for($this->country)->count(5)->sequence(
        ['legal_name' => 'Alpha'],
        ['legal_name' => 'Bravo'],
        ['legal_name' => 'Charlie'],
        ['legal_name' => 'Delta'],
        ['legal_name' => 'Echo'],
    )->create();

    $page1 = $this->engine->searchCompanies($this->country, new SearchCompaniesData(per_page: 2));
    $page1Names = collect($page1->items())->pluck('legal_name')->all();

    $page2 = $this->engine->searchCompanies($this->country, new SearchCompaniesData(per_page: 2, cursor: $page1->nextCursor()?->encode()));
    $page2Names = collect($page2->items())->pluck('legal_name')->all();

    expect($page1Names)->toHaveCount(2)
        ->and($page2Names)->toHaveCount(2)
        ->and(array_intersect($page1Names, $page2Names))->toBeEmpty();
});

it('paginates by cursor across two pages without duplicates or gaps, ranked by a search term', function (): void {
    Company::factory()->for($this->country)->count(5)->sequence(
        ['legal_name' => 'Lyon Transport Alpha'],
        ['legal_name' => 'Lyon Transport Bravo'],
        ['legal_name' => 'Lyon Transport Charlie'],
        ['legal_name' => 'Lyon Transport Delta'],
        ['legal_name' => 'Lyon Transport Echo'],
    )->create();

    $page1 = $this->engine->searchCompanies($this->country, new SearchCompaniesData(term: 'Lyon Transport', per_page: 2));
    $page1Names = collect($page1->items())->pluck('legal_name')->all();

    $page2 = $this->engine->searchCompanies($this->country, new SearchCompaniesData(term: 'Lyon Transport', per_page: 2, cursor: $page1->nextCursor()?->encode()));
    $page2Names = collect($page2->items())->pluck('legal_name')->all();

    expect($page1Names)->toHaveCount(2)
        ->and($page2Names)->toHaveCount(2)
        ->and(array_intersect($page1Names, $page2Names))->toBeEmpty();
});

it('autocompletes with a small ranked list of suggestions', function (): void {
    $city = City::factory()->for($this->country)->create(['name' => 'Lyon']);
    Company::factory()->for($this->country)->create(['legal_name' => 'Keolis Lyon', 'city_id' => $city->id]);
    Company::factory()->for($this->country)->create(['legal_name' => 'Autre Société']);

    $suggestions = $this->engine->autocomplete($this->country, 'Keolis');

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions->first()->legalName)->toBe('Keolis Lyon')
        ->and($suggestions->first()->cityName)->toBe('Lyon');
});

it('returns an empty collection for a blank autocomplete term', function (): void {
    expect($this->engine->autocomplete($this->country, '   '))->toBeEmpty();
});
