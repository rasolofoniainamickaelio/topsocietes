<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Actions\GenerateCityActivityContentAction;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;

class GenerateCityActivityContentCommand extends Command
{
    protected $signature = 'ai:generate-city-activity
        {city : Slug de la ville}
        {country : Sous-domaine du pays, ex. fr}
        {type : activity ou sector}
        {slug : Slug de l\'activité ou du secteur}
        {section=local_overview : Clé de section}';

    protected $description = 'Génère le contenu IA croisé ville×activité (ou ville×secteur), en exécution directe (synchrone, pour test manuel)';

    public function handle(GenerateCityActivityContentAction $action): int
    {
        $country = Country::query()
            ->where('subdomain', $this->argument('country'))
            ->where('is_active', true)
            ->first();

        if ($country === null) {
            $this->error("Pays actif introuvable pour le sous-domaine « {$this->argument('country')} ».");

            return self::FAILURE;
        }

        $city = City::query()->where('country_id', $country->id)->where('slug', $this->argument('city'))->first();

        if ($city === null) {
            $this->error("Ville « {$this->argument('city')} » introuvable pour ce pays.");

            return self::FAILURE;
        }

        $subject = match ($this->argument('type')) {
            'activity' => Activity::query()->where('slug', $this->argument('slug'))->first(),
            'sector' => Sector::query()->where('slug', $this->argument('slug'))->first(),
            default => null,
        };

        if ($subject === null) {
            $this->error('Type invalide (attendu : activity ou sector) ou slug introuvable.');

            return self::FAILURE;
        }

        $section = ContentSection::tryFrom((string) $this->argument('section'));

        if ($section === null) {
            $this->error("Section inconnue : {$this->argument('section')}.");

            return self::FAILURE;
        }

        $job = $action->execute($city, $subject, $section);

        $this->info("Génération terminée : statut {$job->status->value}.");

        if ($job->error_message !== null) {
            $this->line("  {$job->error_message}");
        }

        return self::SUCCESS;
    }
}
