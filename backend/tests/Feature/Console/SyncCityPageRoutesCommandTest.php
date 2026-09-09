<?php

declare(strict_types=1);

use App\Domain\Geo\Jobs\SyncCityPageRouteJob;
use App\Domain\Geo\Models\City;
use App\Domain\Seo\Models\PagePublicationDecision;
use App\Domain\Seo\Models\PageRoute;
use Illuminate\Support\Facades\Queue;

it('queues route synchronization only for cities without an existing route', function (): void {
    // Créées normalement (QUEUE_CONNECTION=sync en test) : `CityObserver`
    // synchronise déjà leur route à la volée.
    $withoutRoute = City::factory()->create();
    $withRoute = City::factory()->create();

    // Simule le trou réel constaté en dev (Phase 16) : une ville existe
    // mais sa route ne l'a jamais été. `page_publication_decisions`
    // référence la route en clé étrangère (sans cascade) : à retirer
    // d'abord.
    $route = PageRoute::query()
        ->where('entity_type', $withoutRoute->getMorphClass())
        ->where('entity_id', $withoutRoute->id)
        ->firstOrFail();
    PagePublicationDecision::query()->where('route_id', $route->id)->delete();
    $route->delete();

    Queue::fake();

    $this->artisan('seo:sync-city-routes')->assertExitCode(0);

    Queue::assertPushed(
        SyncCityPageRouteJob::class,
        fn (SyncCityPageRouteJob $job): bool => $job->city->is($withoutRoute),
    );
    Queue::assertNotPushed(
        SyncCityPageRouteJob::class,
        fn (SyncCityPageRouteJob $job): bool => $job->city->is($withRoute),
    );
});

it('queues nothing when every city already has a route', function (): void {
    City::factory()->create();
    Queue::fake();

    $this->artisan('seo:sync-city-routes')->assertExitCode(0);

    Queue::assertNotPushed(SyncCityPageRouteJob::class);
});
