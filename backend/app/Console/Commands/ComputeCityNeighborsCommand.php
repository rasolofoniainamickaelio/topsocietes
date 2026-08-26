<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Jobs\ComputeCityNeighborsJob;
use App\Domain\Geo\Models\City;
use Illuminate\Console\Command;

class ComputeCityNeighborsCommand extends Command
{
    protected $signature = 'geo:compute-city-neighbors';

    protected $description = 'Met en file la régénération du cache des villes voisines (city_neighbors) pour toutes les communes';

    public function handle(): int
    {
        $count = 0;

        City::query()->cursor()->each(function (City $city) use (&$count): void {
            ComputeCityNeighborsJob::dispatch($city);
            $count++;
        });

        $this->info("{$count} commune(s) mise(s) en file pour recalcul des villes voisines.");

        return self::SUCCESS;
    }
}
