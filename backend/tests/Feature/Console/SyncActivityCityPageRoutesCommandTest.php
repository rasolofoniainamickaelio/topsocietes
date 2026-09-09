<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Seo\Jobs\SyncActivityCityPageRouteJob;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Support\Facades\Queue;

it('queues exactly one sync per distinct city and activity pair', function (): void {
    $city = City::factory()->create();
    $activity = Activity::factory()->create();

    // Deux entreprises partagent la même paire : une seule synchronisation
    // attendue, pas une par entreprise.
    Company::factory()->for($city, 'city')->for($activity)->create();
    Company::factory()->for($city, 'city')->for($activity)->create();

    Queue::fake();

    $this->artisan('seo:sync-activity-city-routes')->assertExitCode(0);

    Queue::assertPushed(SyncActivityCityPageRouteJob::class, 1);
});

it('queues nothing for a company without both a city and an activity resolved', function (): void {
    Company::factory()->create(['city_id' => null]);

    Queue::fake();

    $this->artisan('seo:sync-activity-city-routes')->assertExitCode(0);

    Queue::assertNotPushed(SyncActivityCityPageRouteJob::class);
});
