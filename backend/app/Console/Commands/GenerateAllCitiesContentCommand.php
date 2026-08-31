<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Jobs\GenerateCityContentJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Console\Command;

class GenerateAllCitiesContentCommand extends Command
{
    protected $signature = 'ai:generate-cities {country : Sous-domaine du pays, ex. fr} {section=history : Clé de section (history, nature, leisure, specialty, stats, faq)}';

    protected $description = "Met en file la génération IA d'une section pour toutes les villes d'un pays";

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

        $section = ContentSection::tryFrom((string) $this->argument('section'));

        if ($section === null) {
            $this->error("Section inconnue : {$this->argument('section')}.");

            return self::FAILURE;
        }

        $count = 0;

        City::query()
            ->where('country_id', $country->id)
            ->cursor()
            ->each(function (City $city) use ($section, &$count): void {
                GenerateCityContentJob::dispatch($city, $section);
                $count++;
            });

        $this->info("{$count} ville(s) mise(s) en file pour génération.");

        return self::SUCCESS;
    }
}
