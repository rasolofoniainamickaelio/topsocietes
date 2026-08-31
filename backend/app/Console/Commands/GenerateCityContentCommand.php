<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Actions\GenerateCityContentAction;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Console\Command;

class GenerateCityContentCommand extends Command
{
    protected $signature = 'ai:generate-city {slug : Slug de la ville} {country : Sous-domaine du pays, ex. fr} {section=history : Clé de section (history, nature, leisure, specialty, stats, faq)}';

    protected $description = "Génère le contenu IA d'une section pour une ville, en exécution directe (synchrone, pour test manuel)";

    public function handle(GenerateCityContentAction $action): int
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

        $section = ContentSection::tryFrom((string) $this->argument('section'));

        if ($section === null) {
            $this->error("Section inconnue : {$this->argument('section')}.");

            return self::FAILURE;
        }

        $job = $action->execute($city, $section);

        $this->info("Génération terminée : statut {$job->status->value}.");

        if ($job->error_message !== null) {
            $this->line("  {$job->error_message}");
        }

        return self::SUCCESS;
    }
}
