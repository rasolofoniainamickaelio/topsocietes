<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Jobs\SyncCityPageRouteJob;
use App\Domain\Geo\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill (Phase 13) : miroir de `SyncCompanyPageRoutesCommand` (Phase 16)
 * — aucun Observer n'existait avant cette phase pour `City`, donc toute
 * ville seedée avant son ajout n'a jamais eu sa route synchronisée.
 */
class SyncCityPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-city-routes';

    protected $description = "Met en file la synchronisation de route pour chaque ville qui n'en a pas encore une (Phase 13/18)";

    public function handle(): int
    {
        $count = 0;

        City::query()
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.entity_id', 'cities.id')
                    ->where('routes.entity_type', (new City)->getMorphClass());
            })
            ->cursor()
            ->each(function (City $city) use (&$count): void {
                SyncCityPageRouteJob::dispatch($city);
                $count++;
            });

        $this->info("{$count} ville(s) mise(s) en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
