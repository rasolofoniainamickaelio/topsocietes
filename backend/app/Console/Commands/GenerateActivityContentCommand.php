<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Actions\GenerateActivityContentAction;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;

class GenerateActivityContentCommand extends Command
{
    protected $signature = 'ai:generate-activity
        {type : activity ou sector}
        {slug : Slug de l\'activité ou du secteur}
        {section=understanding_sector : Clé de section}
        {--country= : Sous-domaine du pays pour un contenu spécifique (sinon générique)}';

    protected $description = "Génère le contenu IA d'une section pour une activité ou un secteur, en exécution directe (synchrone, pour test manuel)";

    public function handle(GenerateActivityContentAction $action): int
    {
        $subject = match ($this->argument('type')) {
            'activity' => Activity::query()->where('slug', $this->argument('slug'))->first(),
            'sector' => Sector::query()->where('slug', $this->argument('slug'))->first(),
            default => null,
        };

        if ($subject === null) {
            $this->error('Type invalide (attendu : activity ou sector) ou slug introuvable.');

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

        $section = ContentSection::tryFrom((string) $this->argument('section'));

        if ($section === null) {
            $this->error("Section inconnue : {$this->argument('section')}.");

            return self::FAILURE;
        }

        $job = $action->execute($subject, $country, $section);

        $this->info("Génération terminée : statut {$job->status->value}.");

        if ($job->error_message !== null) {
            $this->line("  {$job->error_message}");
        }

        return self::SUCCESS;
    }
}
