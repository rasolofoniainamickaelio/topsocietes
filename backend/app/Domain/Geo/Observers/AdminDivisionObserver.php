<?php

declare(strict_types=1);

namespace App\Domain\Geo\Observers;

use App\Domain\Geo\Jobs\SyncAdminDivisionPageRouteJob;
use App\Domain\Geo\Models\AdminDivision;

/**
 * Miroir de `CityObserver` (Phase 13) pour `AdminDivision`.
 */
class AdminDivisionObserver
{
    public function created(AdminDivision $division): void
    {
        SyncAdminDivisionPageRouteJob::dispatch($division);
    }

    public function updated(AdminDivision $division): void
    {
        if ($division->isDirty('slug')) {
            SyncAdminDivisionPageRouteJob::dispatch($division);
        }
    }
}
