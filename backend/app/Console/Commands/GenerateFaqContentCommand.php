<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Actions\GenerateActivityContentAction;
use App\Domain\Ai\Actions\GenerateCityActivityContentAction;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;

/**
 * La FAQ n'est pas un type de cible à part (Phase 10) : c'est la section
 * `faq`, partagée par `activity_contents` et `city_activity_contents`
 * (`ContentSection::Faq`) — cette commande route donc vers l'Action déjà
 * responsable de la table concernée plutôt que de dupliquer leur logique.
 */
class GenerateFaqContentCommand extends Command
{
    protected $signature = 'ai:generate-faq
        {type : activity ou sector}
        {slug : Slug de l\'activité ou du secteur}
        {--city= : Slug d\'une ville, pour une FAQ croisée ville×activité (sinon une FAQ générale sur l\'activité/secteur)}
        {--country= : Sous-domaine du pays (ignoré avec --city, obligatoire avec --city)}';

    protected $description = "Génère la FAQ IA d'une activité, d'un secteur, ou de leur croisement avec une ville";

    public function handle(GenerateActivityContentAction $activityAction, GenerateCityActivityContentAction $cityActivityAction): int
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

        if ($this->option('country') === null && $this->option('city') === null) {
            $this->error('Précisez --country (contenu générique par pays) ou --city (croisé ville×activité).');

            return self::FAILURE;
        }

        $country = Country::query()->where('subdomain', $this->option('country'))->where('is_active', true)->first();

        if ($this->option('country') !== null && $country === null) {
            $this->error("Pays actif introuvable pour le sous-domaine « {$this->option('country')} ».");

            return self::FAILURE;
        }

        if ($this->option('city') !== null) {
            if ($country === null) {
                $this->error('--city nécessite --country pour résoudre la ville.');

                return self::FAILURE;
            }

            $city = City::query()->where('country_id', $country->id)->where('slug', $this->option('city'))->first();

            if ($city === null) {
                $this->error("Ville « {$this->option('city')} » introuvable pour ce pays.");

                return self::FAILURE;
            }

            $job = $cityActivityAction->execute($city, $subject, ContentSection::Faq);
        } else {
            $job = $activityAction->execute($subject, $country, ContentSection::Faq);
        }

        $this->info("Génération terminée : statut {$job->status->value}.");

        if ($job->error_message !== null) {
            $this->line("  {$job->error_message}");
        }

        return self::SUCCESS;
    }
}
