<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Seo\Jobs\SyncCompanyPageRouteJob;
use App\Domain\Seo\Models\PagePublicationDecision;
use App\Domain\Seo\Models\PageRoute;
use Illuminate\Support\Facades\Queue;

it('queues route synchronization only for companies without an existing route', function (): void {
    // Créées normalement (QUEUE_CONNECTION=sync en test) : `CompanyObserver`
    // synchronise déjà leur route à la volée.
    $withoutRoute = Company::factory()->create();
    $withRoute = Company::factory()->create();

    // Simule le trou réel constaté en dev : une entreprise existe mais sa
    // route ne l'a jamais été (job perdu, seed en insertion brute qui
    // contourne les events Eloquent...). `page_publication_decisions`
    // référence la route en clé étrangère (sans cascade) : à retirer
    // d'abord.
    $route = PageRoute::query()
        ->where('entity_type', $withoutRoute->getMorphClass())
        ->where('entity_id', $withoutRoute->id)
        ->firstOrFail();
    PagePublicationDecision::query()->where('route_id', $route->id)->delete();
    $route->delete();

    Queue::fake();

    $this->artisan('seo:sync-company-routes')->assertExitCode(0);

    Queue::assertPushed(
        SyncCompanyPageRouteJob::class,
        fn (SyncCompanyPageRouteJob $job): bool => $job->company->is($withoutRoute),
    );
    Queue::assertNotPushed(
        SyncCompanyPageRouteJob::class,
        fn (SyncCompanyPageRouteJob $job): bool => $job->company->is($withRoute),
    );
});

it('queues nothing when every company already has a route', function (): void {
    Company::factory()->create();
    Queue::fake();

    $this->artisan('seo:sync-company-routes')->assertExitCode(0);

    Queue::assertNotPushed(SyncCompanyPageRouteJob::class);
});
