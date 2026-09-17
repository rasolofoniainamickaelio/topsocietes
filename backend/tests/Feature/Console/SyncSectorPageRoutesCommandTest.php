<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Jobs\SyncSectorPageRouteJob;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Facades\Queue;

it('queues one route per active country for a sector missing it, and skips inactive countries', function (): void {
    $active = Country::factory()->create(['is_active' => true]);
    $inactive = Country::factory()->create(['is_active' => false]);

    // Créé normalement (QUEUE_CONNECTION=sync en test) : `SectorObserver`
    // synchronise déjà sa route pour chaque pays actif à la volée.
    $sector = Sector::factory()->create();

    Queue::fake();

    $this->artisan('seo:sync-sector-routes')->assertExitCode(0);

    // Déjà synchronisé par l'Observer à la création : rien à mettre en file.
    Queue::assertNotPushed(SyncSectorPageRouteJob::class);
});

it('queues a missing pair when a new active country is added after the sector already exists', function (): void {
    Country::factory()->create(['is_active' => true]);
    $sector = Sector::factory()->create();

    $newCountry = Country::factory()->create(['is_active' => true]);

    Queue::fake();

    $this->artisan('seo:sync-sector-routes')->assertExitCode(0);

    Queue::assertPushed(
        SyncSectorPageRouteJob::class,
        fn (SyncSectorPageRouteJob $job): bool => $job->sector->is($sector) && $job->country->is($newCountry),
    );
});
