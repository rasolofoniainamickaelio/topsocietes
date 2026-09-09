<?php

declare(strict_types=1);

namespace App\Domain\Seo\Jobs;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\SyncActivityCityPageRouteAction;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Miroir de `SyncCompanyPageRouteJob` pour une page composite (Phase 12) —
 * jamais synchrone sur une requête (Phase 18).
 */
class SyncActivityCityPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly City $city,
        public readonly Activity $activity,
        public readonly Country $country,
    ) {
        $this->onQueue('geo');
    }

    public function handle(SyncActivityCityPageRouteAction $sync): void
    {
        $sync->execute($this->city, $this->activity, $this->country);
    }
}
