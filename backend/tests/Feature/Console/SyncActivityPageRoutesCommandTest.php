<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Models\PagePublicationDecision;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Jobs\SyncActivityPageRouteJob;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;
use Illuminate\Support\Facades\Queue;

it('queues route synchronization only for activities without an existing route', function (): void {
    $country = Country::factory()->create();
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);

    // Créées normalement (QUEUE_CONNECTION=sync en test) : `ActivityObserver`
    // synchronise déjà leur route à la volée.
    $withoutRoute = Activity::factory()->for($nomenclature, 'nomenclature')->create();
    $withRoute = Activity::factory()->for($nomenclature, 'nomenclature')->create();

    $route = PageRoute::query()
        ->where('entity_type', $withoutRoute->getMorphClass())
        ->where('entity_id', $withoutRoute->id)
        ->firstOrFail();
    PagePublicationDecision::query()->where('route_id', $route->id)->delete();
    $route->delete();

    Queue::fake();

    $this->artisan('seo:sync-activity-routes')->assertExitCode(0);

    Queue::assertPushed(
        SyncActivityPageRouteJob::class,
        fn (SyncActivityPageRouteJob $job): bool => $job->activity->is($withoutRoute),
    );
    Queue::assertNotPushed(
        SyncActivityPageRouteJob::class,
        fn (SyncActivityPageRouteJob $job): bool => $job->activity->is($withRoute),
    );
});

it('queues nothing when every activity already has a route', function (): void {
    $country = Country::factory()->create();
    $nomenclature = ActivityNomenclature::factory()->create(['country_id' => $country->id]);
    Activity::factory()->for($nomenclature, 'nomenclature')->create();
    Queue::fake();

    $this->artisan('seo:sync-activity-routes')->assertExitCode(0);

    Queue::assertNotPushed(SyncActivityPageRouteJob::class);
});
