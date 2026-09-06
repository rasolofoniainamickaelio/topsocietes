<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Jobs\ComputeCompanyNearbyPoisJob;
use App\Domain\Geo\Jobs\ResolveCompanyDistrictJob;
use App\Domain\Geo\Models\City;
use App\Domain\Seo\Models\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('dispatches district resolution when a company is created with a city already resolved', function (): void {
    Queue::fake();
    $city = City::factory()->create();

    Company::factory()->create([
        'city_id' => $city->id,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);

    Queue::assertPushed(ResolveCompanyDistrictJob::class);
});

it('dispatches nothing on creation when no city is resolved', function (): void {
    Queue::fake();
    Company::factory()->create();

    Queue::assertNotPushed(ResolveCompanyDistrictJob::class);
});

it('dispatches district resolution when location changes', function (): void {
    Queue::fake();
    $company = Company::factory()->create();

    $company->update(['location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography')]);

    Queue::assertPushed(ResolveCompanyDistrictJob::class);
});

it('dispatches nearby POI computation when a company becomes published', function (): void {
    Queue::fake();
    $company = Company::factory()->create([
        'content_status' => CompanyContentStatus::Pending,
        'is_indexable' => true,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(2.35, 48.87), 4326)::geography'),
    ]);

    $company->update(['content_status' => CompanyContentStatus::Published]);

    Queue::assertPushed(ComputeCompanyNearbyPoisJob::class);
});

it('dispatches nothing when an unrelated field changes', function (): void {
    Queue::fake();
    $company = Company::factory()->create();

    $company->update(['legal_name' => 'Something Else']);

    Queue::assertNotPushed(ResolveCompanyDistrictJob::class);
    Queue::assertNotPushed(ComputeCompanyNearbyPoisJob::class);
});

it('creates a redirect from the old path to the new one when the slug changes', function (): void {
    $company = Company::factory()->create(['slug' => 'ancien-nom', 'city_id' => null]);

    $company->update(['slug' => 'nouveau-nom']);

    $redirect = Redirect::query()->where('country_id', $company->country_id)->firstOrFail();
    expect($redirect->from_path)->toBe("/entreprise/ancien-nom-{$company->public_id}")
        ->and($redirect->to_path)->toBe("/entreprise/nouveau-nom-{$company->public_id}");
});

it('creates no redirect when the slug does not change', function (): void {
    $company = Company::factory()->create(['slug' => 'stable']);

    $company->update(['legal_name' => 'Autre nom légal']);

    expect(Redirect::query()->count())->toBe(0);
});
