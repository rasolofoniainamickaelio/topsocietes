<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Ai\Models\AiGenerationLog;
use App\Domain\Content\Models\Fact;
use Illuminate\Support\Collection;

/**
 * Trace exacte d'une tentative de génération (prompt envoyé, faits utilisés,
 * sortie brute, rapport de validation) — extraite de la logique
 * d'orchestration pour être réutilisée par tous les types de génération
 * (Phase 10) sans dupliquer la mise en forme du payload.
 */
class LogGenerationAttemptAction
{
    /**
     * @param  Collection<int, Fact>  $facts
     * @param  array<string, mixed>|null  $validationReport
     */
    public function execute(AiGenerationJob $job, string $userPrompt, Collection $facts, ?string $rawOutput, ?array $validationReport): void
    {
        AiGenerationLog::create([
            'generation_job_id' => $job->id,
            'input_payload' => [
                'prompt' => $userPrompt,
                'facts' => $facts->map(fn (Fact $fact) => ['key' => $fact->key, 'value' => $fact->value])->all(),
            ],
            'raw_output' => $rawOutput,
            'validation_report' => $validationReport,
        ]);
    }
}
