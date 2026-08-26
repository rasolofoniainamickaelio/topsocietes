<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyNearbyPoi;
use App\Domain\Geo\Actions\ComputeCompanyNearbyPoisAction;
use App\Domain\Geo\Models\PointOfInterest;
use Illuminate\Support\Facades\DB;

it('includes a publishable POI within the radius', function (): void {
    $company = Company::factory()->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);
    $poi = PointOfInterest::factory()->create([
        'is_publishable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.351, 48.871), 4326)::geography'),
    ]);

    app(ComputeCompanyNearbyPoisAction::class)->execute($company);

    expect(CompanyNearbyPoi::query()->where('company_id', $company->id)->pluck('poi_id'))
        ->toContain($poi->id);
});

it('excludes a POI outside the radius', function (): void {
    $company = Company::factory()->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.3522, 48.8566), 4326)::geography'),
    ]);
    $farPoi = PointOfInterest::factory()->create([
        'is_publishable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(4.8357, 45.7640), 4326)::geography'),
    ]);

    app(ComputeCompanyNearbyPoisAction::class)->execute($company);

    expect(CompanyNearbyPoi::query()->where('company_id', $company->id)->pluck('poi_id'))
        ->not->toContain($farPoi->id);
});

it('excludes a non-publishable POI', function (): void {
    $company = Company::factory()->create([
        'content_status' => CompanyContentStatus::Published,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);
    $hiddenPoi = PointOfInterest::factory()->create([
        'is_publishable' => false,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.351, 48.871), 4326)::geography'),
    ]);

    app(ComputeCompanyNearbyPoisAction::class)->execute($company);

    expect(CompanyNearbyPoi::query()->where('company_id', $company->id)->pluck('poi_id'))
        ->not->toContain($hiddenPoi->id);
});

it('generates nothing for a company that is not published', function (): void {
    $company = Company::factory()->create([
        'content_status' => CompanyContentStatus::Pending,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);
    PointOfInterest::factory()->create([
        'is_publishable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.351, 48.871), 4326)::geography'),
    ]);

    app(ComputeCompanyNearbyPoisAction::class)->execute($company);

    expect(CompanyNearbyPoi::query()->where('company_id', $company->id)->count())->toBe(0);
});
