<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Seo\Jobs\SyncActivityCityPageRouteJob;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Console\Command;

/**
 * Backfill (Phase 12) : une page ville×activité n'a pas d'événement Eloquent
 * "created" pour se déclencher (page composite, pas d'entité unique) —
 * cette commande énumère les paires (ville, activité) qui existent
 * réellement, d'après les entreprises elles-mêmes, plutôt que de tenter de
 * couvrir toutes les combinaisons théoriques.
 */
class SyncActivityCityPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-activity-city-routes';

    protected $description = 'Met en file la synchronisation de route pour chaque paire ville×activité réellement présente dans les entreprises';

    public function handle(): int
    {
        $count = 0;

        Company::query()
            ->whereNotNull('city_id')
            ->whereNotNull('activity_id')
            ->select('city_id', 'activity_id')
            ->distinct()
            ->cursor()
            ->each(function (Company $pair) use (&$count): void {
                $city = City::query()->with('country')->find($pair->city_id);
                $activity = Activity::query()->find($pair->activity_id);

                if ($city === null || $activity === null) {
                    return;
                }

                SyncActivityCityPageRouteJob::dispatch($city, $activity, $city->country);
                $count++;
            });

        $this->info("{$count} paire(s) ville×activité mise(s) en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
