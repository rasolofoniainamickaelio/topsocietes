<?php

declare(strict_types=1);

namespace App\Domain\Seo\Jobs;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncSectorPageRouteAction;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Miroir de `SyncActivityCityPageRouteJob` — un secteur étant transversal,
 * une route est synchronisée par pays actif, jamais synchrone sur une
 * requête (Phase 18).
 */
class SyncSectorPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Sector $sector,
        public readonly Country $country,
    ) {
        $this->onQueue('geo');
    }

    public function handle(SyncSectorPageRouteAction $sync): void
    {
        $sync->execute($this->sector, $this->country);
    }
}
