<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Content\Jobs\CollectCityFactsJob;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Console\Command;

class CollectAllCitiesFactsCommand extends Command
{
    protected $signature = 'sources:collect-cities {country : Sous-domaine du pays, ex. fr}';

    protected $description = "Met en file la collecte de faits sourcés pour toutes les villes d'un pays";

    public function handle(): int
    {
        $country = Country::query()
            ->where('subdomain', $this->argument('country'))
            ->where('is_active', true)
            ->first();

        if ($country === null) {
            $this->error("Pays actif introuvable pour le sous-domaine « {$this->argument('country')} ».");

            return self::FAILURE;
        }

        $count = 0;

        City::query()
            ->where('country_id', $country->id)
            ->cursor()
            ->each(function (City $city) use (&$count): void {
                CollectCityFactsJob::dispatch($city);
                $count++;
            });

        $this->info("{$count} ville(s) mise(s) en file pour collecte de faits.");

        return self::SUCCESS;
    }
}
