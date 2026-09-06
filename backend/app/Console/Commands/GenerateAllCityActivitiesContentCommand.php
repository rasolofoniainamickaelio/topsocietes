<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Jobs\GenerateCityActivityContentJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;

class GenerateAllCityActivitiesContentCommand extends Command
{
    protected $signature = 'ai:generate-city-activities
        {country : Sous-domaine du pays, ex. fr}
        {type : activity ou sector}
        {slug : Slug de l\'activité ou du secteur}
        {section=local_overview : Clé de section}';

    protected $description = "Met en file la génération IA croisée ville×activité (ou ville×secteur) pour toutes les villes d'un pays";

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

        $count = 0;

        City::query()
            ->where('country_id', $country->id)
            ->cursor()
            ->each(function (City $city) use ($subject, $section, &$count): void {
                GenerateCityActivityContentJob::dispatch($city, $subject, $section);
                $count++;
            });

        $this->info("{$count} ville(s) mise(s) en file pour génération.");

        return self::SUCCESS;
    }
}
