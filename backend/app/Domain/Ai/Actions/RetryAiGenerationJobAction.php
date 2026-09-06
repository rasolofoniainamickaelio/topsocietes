<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Enums\GenerationMode;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Jobs\GenerateActivityContentJob;
use App\Domain\Ai\Jobs\GenerateCityActivityContentJob;
use App\Domain\Ai\Jobs\GenerateCityContentJob;
use App\Domain\Ai\Jobs\TransformContentJob;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

/**
 * Généralise la relance manuelle (jusqu'ici limitée aux villes par
 * `ai:retry-failed`) à tous les types de génération, pour le bouton
 * « Relancer » du back-office (Phase 21). Ne retraite qu'un statut
 * `Failed` (incident technique) — un `Rejected` est un refus de contenu
 * du modèle, pas une panne, et ne se relance pas à l'identique.
 *
 * `AiGenerationJob` ne conserve pas le pays utilisé pour une génération
 * activité/secteur (pas de colonne `country_id`) : la relance d'un tel job
 * reproduit donc toujours un contenu générique (`$country = null`), jamais
 * le contenu spécifique-pays d'origine s'il y en avait un.
 */
class RetryAiGenerationJobAction
{
    public function execute(AiGenerationJob $job): AiGenerationJob
    {
        if ($job->status !== GenerationStatus::Failed) {
            throw new InvalidArgumentException('Seule une génération en échec technique (Failed) peut être relancée.');
        }

        $mode = $job->mode ?? GenerationMode::Create;

        if ($mode === GenerationMode::Create) {
            $this->retryCreate($job);
        } else {
            $this->retryTransform($job, $mode);
        }

        $job->update(['attempts' => $job->attempts + 1]);

        return $job->fresh();
    }

    private function retryCreate(AiGenerationJob $job): void
    {
        $section = ContentSection::from($job->section);

        $cityMorph = (new City)->getMorphClass();
        $activityMorph = (new Activity)->getMorphClass();
        $sectorMorph = (new Sector)->getMorphClass();

        if ($job->target_type === $cityMorph && $job->activity_id === null && $job->sector_id === null) {
            GenerateCityContentJob::dispatch(City::query()->findOrFail($job->target_id), $section);

            return;
        }

        if ($job->target_type === $cityMorph) {
            $city = City::query()->findOrFail($job->target_id);
            $subject = $job->activity_id !== null
                ? Activity::query()->findOrFail($job->activity_id)
                : Sector::query()->findOrFail($job->sector_id);

            GenerateCityActivityContentJob::dispatch($city, $subject, $section);

            return;
        }

        if ($job->target_type === $activityMorph || $job->target_type === $sectorMorph) {
            $subject = $job->target_type === $activityMorph
                ? Activity::query()->findOrFail($job->target_id)
                : Sector::query()->findOrFail($job->target_id);

            GenerateActivityContentJob::dispatch($subject, null, $section);

            return;
        }

        throw new InvalidArgumentException("Type de cible non pris en charge pour la relance : {$job->target_type}");
    }

    private function retryTransform(AiGenerationJob $job, GenerationMode $mode): void
    {
        $modelClass = Relation::getMorphedModel($job->target_type);

        if ($modelClass === null) {
            throw new InvalidArgumentException("Type de cible inconnu dans le morph map : {$job->target_type}");
        }

        $content = $modelClass::query()->find($job->target_id);

        if ($content === null) {
            throw new InvalidArgumentException('Le contenu à transformer a été supprimé depuis cette génération.');
        }

        TransformContentJob::dispatch($content, $mode);
    }
}
