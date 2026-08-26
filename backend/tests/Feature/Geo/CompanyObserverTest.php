<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Jobs\ComputeCompanyNearbyPoisJob;
use App\Domain\Geo\Jobs\ResolveCompanyDistrictJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

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
