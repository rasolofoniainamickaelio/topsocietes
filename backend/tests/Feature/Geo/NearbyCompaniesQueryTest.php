<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Queries\NearbyCompaniesQuery;
use Illuminate\Support\Facades\DB;

it('includes a publishable company within the radius, ordered by distance', function (): void {
    $country = Country::factory()->create();
    $company = Company::factory()->for($country)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.3522, 48.8566), 4326)::geography'),
    ])->fresh();

    $near = Company::factory()->for($country)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.353, 48.857), 4326)::geography'),
    ]);
    $far = Company::factory()->for($country)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(4.8357, 45.7640), 4326)::geography'),
    ]);

    $results = app(NearbyCompaniesQuery::class)->execute($company);

    expect($results->pluck('id'))->toContain($near->id)
        ->and($results->pluck('id'))->not->toContain($far->id)
        ->and($results->pluck('id'))->not->toContain($company->id);
});

it('excludes a non-published company', function (): void {
    $country = Country::factory()->create();
    $company = Company::factory()->for($country)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.3522, 48.8566), 4326)::geography'),
    ])->fresh();

    $hidden = Company::factory()->for($country)->create([
        'content_status' => CompanyContentStatus::Pending,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.353, 48.857), 4326)::geography'),
    ]);

    $results = app(NearbyCompaniesQuery::class)->execute($company);

    expect($results->pluck('id'))->not->toContain($hidden->id);
});

it('excludes a company from a different country even if very close', function (): void {
    $country = Country::factory()->create();
    $otherCountry = Country::factory()->create();

    $company = Company::factory()->for($country)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.3522, 48.8566), 4326)::geography'),
    ])->fresh();

    $otherCountryCompany = Company::factory()->for($otherCountry)->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.353, 48.857), 4326)::geography'),
    ]);

    $results = app(NearbyCompaniesQuery::class)->execute($company);

    expect($results->pluck('id'))->not->toContain($otherCountryCompany->id);
});

it('returns an empty collection when the company has no location', function (): void {
    $company = Company::factory()->create();

    $results = app(NearbyCompaniesQuery::class)->execute($company);

    expect($results)->toBeEmpty();
});
