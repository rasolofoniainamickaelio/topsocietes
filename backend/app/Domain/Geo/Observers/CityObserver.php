<?php

declare(strict_types=1);

namespace App\Domain\Geo\Observers;

use App\Domain\Geo\Jobs\SyncCityPageRouteJob;
use App\Domain\Geo\Models\City;

/**
 * Miroir de `CompanyObserver` (Phase 16) pour `City` (Phase 13) : toute
 * ville créée devient une route évaluable, jamais en attente d'une
 * première modification pour exister.
 */
class CityObserver
{
    public function created(City $city): void
    {
        SyncCityPageRouteJob::dispatch($city);
    }

    public function updated(City $city): void
    {
        if ($city->isDirty('slug')) {
            SyncCityPageRouteJob::dispatch($city);
        }
    }
}
