<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Jobs\GenerateActivityContentJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;

class GenerateAllActivitiesContentCommand extends Command
{
    protected $signature = 'ai:generate-activities
        {type : activity ou sector}
        {section=understanding_sector : Clé de section}
        {--country= : Sous-domaine du pays pour un contenu spécifique (sinon générique, toutes juridictions)}';

    protected $description = "Met en file la génération IA d'une section pour toutes les activités ou tous les secteurs";

    public function handle(): int
    {
        $section = ContentSection::tryFrom((string) $this->argument('section'));

        if ($section === null) {
            $this->error("Section inconnue : {$this->argument('section')}.");

            return self::FAILURE;
        }

        $country = null;

        if ($this->option('country') !== null) {
            $country = Country::query()->where('subdomain', $this->option('country'))->where('is_active', true)->first();

            if ($country === null) {
                $this->error("Pays actif introuvable pour le sous-domaine « {$this->option('country')} ».");

                return self::FAILURE;
            }
        }

        $query = match ($this->argument('type')) {
            'activity' => Activity::query(),
            'sector' => Sector::query(),
            default => null,
        };

        if ($query === null) {
            $this->error('Type invalide (attendu : activity ou sector).');

            return self::FAILURE;
        }

        $count = 0;

        $query->cursor()->each(function (Activity|Sector $subject) use ($country, $section, &$count): void {
            GenerateActivityContentJob::dispatch($subject, $country, $section);
            $count++;
        });

        $this->info("{$count} élément(s) mis en file pour génération.");

        return self::SUCCESS;
    }
}
