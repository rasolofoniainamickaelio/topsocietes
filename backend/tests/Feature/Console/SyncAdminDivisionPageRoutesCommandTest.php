<?php

declare(strict_types=1);

use App\Domain\Geo\Jobs\SyncAdminDivisionPageRouteJob;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Seo\Models\PagePublicationDecision;
use App\Domain\Seo\Models\PageRoute;
use Illuminate\Support\Facades\Queue;

it('queues route synchronization only for admin divisions without an existing route', function (): void {
    // Créées normalement (QUEUE_CONNECTION=sync en test) : `AdminDivisionObserver`
    // synchronise déjà leur route à la volée.
    $withoutRoute = AdminDivision::factory()->create();
    $withRoute = AdminDivision::factory()->create();

    $route = PageRoute::query()
        ->where('entity_type', $withoutRoute->getMorphClass())
        ->where('entity_id', $withoutRoute->id)
        ->firstOrFail();
    PagePublicationDecision::query()->where('route_id', $route->id)->delete();
    $route->delete();

    Queue::fake();

    $this->artisan('seo:sync-admin-division-routes')->assertExitCode(0);

    Queue::assertPushed(
        SyncAdminDivisionPageRouteJob::class,
        fn (SyncAdminDivisionPageRouteJob $job): bool => $job->division->is($withoutRoute),
    );
    Queue::assertNotPushed(
        SyncAdminDivisionPageRouteJob::class,
        fn (SyncAdminDivisionPageRouteJob $job): bool => $job->division->is($withRoute),
    );
});

it('queues nothing when every admin division already has a route', function (): void {
    AdminDivision::factory()->create();
    Queue::fake();

    $this->artisan('seo:sync-admin-division-routes')->assertExitCode(0);

    Queue::assertNotPushed(SyncAdminDivisionPageRouteJob::class);
});
