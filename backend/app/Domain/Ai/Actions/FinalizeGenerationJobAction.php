<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Data\GenerationResultData;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiGenerationJob;

/**
 * Ferme un job de génération avec ses métriques finales — même logique
 * quelle que soit la cible générée (Phase 10), extraite pour éviter que
 * chaque type de génération recalcule sa propre durée/coût.
 */
class FinalizeGenerationJobAction
{
    public function execute(AiGenerationJob $job, GenerationStatus $status, ?GenerationResultData $result = null, ?string $errorMessage = null): void
    {
        $job->update([
            'status' => $status,
            'finished_at' => now(),
            'duration_ms' => (int) $job->started_at->diffInMilliseconds(now()),
            'input_tokens' => $result?->inputTokens,
            'output_tokens' => $result?->outputTokens,
            'cost_cents' => $result?->costCents,
            'error_message' => $errorMessage,
        ]);
    }
}
