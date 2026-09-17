<?php

declare(strict_types=1);

use App\Domain\Geo\Jobs\RecalculateTerritoryCompaniesCountJob;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\District;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Facades\Queue;

it('queues a recalculation job for every city, district, admin division, activity and sector', function (): void {
    $city = City::factory()->create();
    $district = District::factory()->create();
    $division = AdminDivision::factory()->create();
    $activity = Activity::factory()->create();
    $sector = Sector::factory()->create();

    Queue::fake();

    $this->artisan('geo:recalculate-companies-counts')->assertExitCode(0);

    Queue::assertPushed(
        RecalculateTerritoryCompaniesCountJob::class,
        fn (RecalculateTerritoryCompaniesCountJob $job): bool => $job->pageType === PageType::City && $job->entityId === $city->id,
    );
    Queue::assertPushed(
        RecalculateTerritoryCompaniesCountJob::class,
        fn (RecalculateTerritoryCompaniesCountJob $job): bool => $job->pageType === PageType::District && $job->entityId === $district->id,
    );
    Queue::assertPushed(
        RecalculateTerritoryCompaniesCountJob::class,
        fn (RecalculateTerritoryCompaniesCountJob $job): bool => $job->pageType === PageType::AdminDivision && $job->entityId === $division->id,
    );
    Queue::assertPushed(
        RecalculateTerritoryCompaniesCountJob::class,
        fn (RecalculateTerritoryCompaniesCountJob $job): bool => $job->pageType === PageType::Activity && $job->entityId === $activity->id,
    );
    Queue::assertPushed(
        RecalculateTerritoryCompaniesCountJob::class,
        fn (RecalculateTerritoryCompaniesCountJob $job): bool => $job->pageType === PageType::Sector && $job->entityId === $sector->id,
    );
});
