<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Jobs\SyncAdminDivisionPageRouteJob;
use App\Domain\Geo\Models\AdminDivision;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill (Phase 13) : miroir de `SyncCityPageRoutesCommand` — aucun
 * Observer n'existait avant cette phase pour `AdminDivision`, donc toute
 * division seedée avant son ajout n'a jamais eu sa route synchronisée.
 */
class SyncAdminDivisionPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-admin-division-routes';

    protected $description = "Met en file la synchronisation de route pour chaque division administrative qui n'en a pas encore une (Phase 13/18)";

    public function handle(): int
    {
        $count = 0;

        AdminDivision::query()
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.entity_id', 'admin_divisions.id')
                    ->where('routes.entity_type', (new AdminDivision)->getMorphClass());
            })
            ->cursor()
            ->each(function (AdminDivision $division) use (&$count): void {
                SyncAdminDivisionPageRouteJob::dispatch($division);
                $count++;
            });

        $this->info("{$count} division(s) administrative(s) mise(s) en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
