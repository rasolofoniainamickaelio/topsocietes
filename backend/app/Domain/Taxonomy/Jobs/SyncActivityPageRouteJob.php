<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Jobs;

use App\Domain\Seo\Actions\BuildActivityPathAction;
use App\Domain\Seo\Actions\SyncPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Miroir de `SyncCityPageRouteJob` (Phase 13) pour `Activity` — une route
 * par pays de sa nomenclature (une activité n'appartient qu'à une seule
 * nomenclature pays, contrairement à `Sector` qui est transversal).
 */
class SyncActivityPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Activity $activity)
    {
        $this->onQueue('geo');
    }

    public function handle(BuildActivityPathAction $buildPath, SyncPageRouteAction $sync): void
    {
        $country = $this->activity->nomenclature->country;

        if ($country === null) {
            return;
        }

        $sync->execute($this->activity, $country, PageType::Activity, $buildPath->execute($this->activity, $country));
    }
}
