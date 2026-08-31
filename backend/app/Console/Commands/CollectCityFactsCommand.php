<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Content\Actions\CollectCityFactsAction;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Console\Command;

class CollectCityFactsCommand extends Command
{
    protected $signature = 'sources:collect-city {slug : Slug de la ville} {country : Sous-domaine du pays, ex. fr}';

    protected $description = "Collecte les faits sourcés (Wikipedia, Wikidata, OSM) d'une ville, en exécution directe (synchrone, pour test manuel)";

    public function handle(CollectCityFactsAction $action): int
    {
        $country = Country::query()
            ->where('subdomain', $this->argument('country'))
            ->where('is_active', true)
            ->first();

        if ($country === null) {
            $this->error("Pays actif introuvable pour le sous-domaine « {$this->argument('country')} ».");

            return self::FAILURE;
        }

        $city = City::query()
            ->where('country_id', $country->id)
            ->where('slug', $this->argument('slug'))
            ->first();

        if ($city === null) {
            $this->error("Ville « {$this->argument('slug')} » introuvable pour ce pays.");

            return self::FAILURE;
        }

        $jobRun = $action->execute($city);

        $this->info("Collecte terminée (statut : {$jobRun->status->value}) :");
        foreach ($jobRun->output ?? [] as $provider => $result) {
            $this->line("  - {$provider} : {$result}");
        }

        return self::SUCCESS;
    }
}
