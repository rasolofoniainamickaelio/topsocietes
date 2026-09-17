<?php

declare(strict_types=1);

use App\Domain\Geo\Jobs\SyncCountryPageRouteJob;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Models\PagePublicationDecision;
use App\Domain\Seo\Models\PageRoute;
use Illuminate\Support\Facades\Queue;

it('queues route synchronization only for countries without an existing route', function (): void {
    // Créés normalement (QUEUE_CONNECTION=sync en test) : `CountryObserver`
    // synchronise déjà leur route à la volée.
    $withoutRoute = Country::factory()->create();
    $withRoute = Country::factory()->create();

    $route = PageRoute::query()
        ->where('entity_type', $withoutRoute->getMorphClass())
        ->where('entity_id', $withoutRoute->id)
        ->firstOrFail();
    PagePublicationDecision::query()->where('route_id', $route->id)->delete();
    $route->delete();

    Queue::fake();

    $this->artisan('seo:sync-country-routes')->assertExitCode(0);

    Queue::assertPushed(
        SyncCountryPageRouteJob::class,
        fn (SyncCountryPageRouteJob $job): bool => $job->country->is($withoutRoute),
    );
    Queue::assertNotPushed(
        SyncCountryPageRouteJob::class,
        fn (SyncCountryPageRouteJob $job): bool => $job->country->is($withRoute),
    );
});

it('queues nothing when every country already has a route', function (): void {
    Country::factory()->create();
    Queue::fake();

    $this->artisan('seo:sync-country-routes')->assertExitCode(0);

    Queue::assertNotPushed(SyncCountryPageRouteJob::class);
});
