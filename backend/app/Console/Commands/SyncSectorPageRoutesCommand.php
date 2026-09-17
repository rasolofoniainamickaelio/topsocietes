<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Jobs\SyncSectorPageRouteJob;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill (Phase 13) : produit cartésien secteur × pays actif — un secteur
 * étant transversal, il lui faut une route par pays, contrairement aux
 * autres backfills qui énumèrent une seule entité.
 */
class SyncSectorPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-sector-routes';

    protected $description = "Met en file la synchronisation de route pour chaque paire secteur×pays qui n'en a pas encore une (Phase 13/18)";

    public function handle(): int
    {
        $count = 0;
        $countries = Country::query()->where('is_active', true)->get();

        Sector::query()->cursor()->each(function (Sector $sector) use ($countries, &$count): void {
            foreach ($countries as $country) {
                $exists = DB::table('routes')
                    ->where('country_id', $country->id)
                    ->where('entity_type', $sector->getMorphClass())
                    ->where('entity_id', $sector->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                SyncSectorPageRouteJob::dispatch($sector, $country);
                $count++;
            }
        });

        $this->info("{$count} paire(s) secteur×pays mise(s) en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
