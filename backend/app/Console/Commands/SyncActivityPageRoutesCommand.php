<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Taxonomy\Jobs\SyncActivityPageRouteJob;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill (Phase 13) : miroir de `SyncCityPageRoutesCommand` — aucun
 * Observer n'existait avant cette phase pour `Activity`, donc toute
 * activité seedée avant son ajout n'a jamais eu sa route synchronisée.
 */
class SyncActivityPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-activity-routes';

    protected $description = "Met en file la synchronisation de route pour chaque activité qui n'en a pas encore une (Phase 13/18)";

    public function handle(): int
    {
        $count = 0;

        Activity::query()
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.entity_id', 'activities.id')
                    ->where('routes.entity_type', (new Activity)->getMorphClass());
            })
            ->cursor()
            ->each(function (Activity $activity) use (&$count): void {
                SyncActivityPageRouteJob::dispatch($activity);
                $count++;
            });

        $this->info("{$count} activité(s) mise(s) en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
