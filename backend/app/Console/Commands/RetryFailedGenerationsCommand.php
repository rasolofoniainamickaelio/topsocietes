<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Jobs\GenerateCityContentJob;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use Illuminate\Console\Command;

/**
 * Relance les générations en échec technique (`Failed` — erreur
 * fournisseur/config, distinct de `Rejected` qui est un refus de fond,
 * pas d'incident). Ne cible que les jobs dont le sujet est une ville :
 * seul type de génération construit cette passe.
 */
class RetryFailedGenerationsCommand extends Command
{
    protected $signature = 'ai:retry-failed';

    protected $description = 'Remet en file les générations IA en échec technique (statut Failed)';

    public function handle(): int
    {
        $count = 0;

        AiGenerationJob::query()
            ->where('status', GenerationStatus::Failed)
            ->where('target_type', (new City)->getMorphClass())
            ->cursor()
            ->each(function (AiGenerationJob $job) use (&$count): void {
                $city = City::query()->find($job->target_id);
                $section = ContentSection::tryFrom($job->section);

                if ($city === null || $section === null) {
                    return;
                }

                GenerateCityContentJob::dispatch($city, $section);
                $job->update(['attempts' => $job->attempts + 1]);
                $count++;
            });

        $this->info("{$count} génération(s) remise(s) en file.");

        return self::SUCCESS;
    }
}
