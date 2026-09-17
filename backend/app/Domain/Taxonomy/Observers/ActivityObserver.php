<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Observers;

use App\Domain\Taxonomy\Jobs\SyncActivityPageRouteJob;
use App\Domain\Taxonomy\Models\Activity;

/**
 * Miroir de `CityObserver` (Phase 13) pour `Activity`.
 */
class ActivityObserver
{
    public function created(Activity $activity): void
    {
        SyncActivityPageRouteJob::dispatch($activity);
    }

    public function updated(Activity $activity): void
    {
        if ($activity->isDirty('slug')) {
            SyncActivityPageRouteJob::dispatch($activity);
        }
    }
}
