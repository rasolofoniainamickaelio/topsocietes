<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Jobs\SyncCountryPageRouteJob;
use App\Domain\Geo\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill (Phase 13) : miroir de `SyncCityPageRoutesCommand` — aucun
 * Observer n'existait avant cette phase pour `Country`, donc tout pays
 * seedé avant son ajout n'a jamais eu sa route (page d'accueil)
 * synchronisée.
 */
class SyncCountryPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-country-routes';

    protected $description = "Met en file la synchronisation de route pour chaque pays qui n'en a pas encore une (Phase 13/18)";

    public function handle(): int
    {
        $count = 0;

        Country::query()
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.entity_id', 'countries.id')
                    ->where('routes.entity_type', (new Country)->getMorphClass());
            })
            ->cursor()
            ->each(function (Country $country) use (&$count): void {
                SyncCountryPageRouteJob::dispatch($country);
                $count++;
            });

        $this->info("{$count} pays mis en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
