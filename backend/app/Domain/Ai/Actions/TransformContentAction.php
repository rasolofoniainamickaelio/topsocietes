<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Contracts\AiDriver;
use App\Domain\Ai\Data\GenerationRequestData;
use App\Domain\Ai\Enums\GenerationMode;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Ai\Support\AiWritableFields;
use App\Domain\Ai\Support\ForbiddenTopics;
use App\Domain\Content\Actions\SnapshotContentRevisionAction;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ContentSourceLink;
use App\Domain\Content\Models\Fact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Réécriture / résumé / contextualisation d'un bloc de contenu DÉJÀ
 * existant (Phase 10) — ne crée jamais de nouvelle ligne, et n'écrit
 * jamais directement en `Published` : le résultat atterrit toujours en
 * `Review`, comme toute sortie IA (CLAUDE.md §6.1/§6.7), même si le
 * contenu d'origine était publié. Réutilise les mêmes faits que la
 * génération d'origine (via `ContentSourceLink`), jamais de nouveaux faits :
 * transformer un texte n'est pas une occasion d'y injecter une information
 * supplémentaire non vérifiée.
 */
class TransformContentAction
{
    public function __construct(
        private readonly AiDriver $driver,
        private readonly ValidateGenerationOutputAction $validator,
        private readonly SnapshotContentRevisionAction $snapshotRevision,
        private readonly LogGenerationAttemptAction $logAttempt,
        private readonly FinalizeGenerationJobAction $finalizeJob,
    ) {}

    public function execute(Model $content, GenerationMode $mode): AiGenerationJob
    {
        if ($mode === GenerationMode::Create) {
            throw new InvalidArgumentException('TransformContentAction ne traite jamais le mode Create.');
        }

        AiWritableFields::assertWritable($content);

        $currentBody = (string) $content->getAttribute('body');

        if (trim($currentBody) === '') {
            throw new InvalidArgumentException('Contenu vide : rien à transformer.');
        }

        $factIds = ContentSourceLink::query()
            ->where('content_type', $content->getMorphClass())
            ->where('content_id', $content->getKey())
            ->pluck('fact_id');

        /** @var Collection<int, Fact> $facts */
        $facts = Fact::query()->whereIn('id', $factIds)->get();

        $prompt = AiPrompt::query()
            ->where('key', "transform_{$mode->value}")
            ->where('is_active', true)
            ->first();

        $job = AiGenerationJob::create([
            'target_type' => $content->getMorphClass(),
            'target_id' => $content->getKey(),
            'section' => (string) $content->getAttribute('section'),
            'mode' => $mode,
            'locale' => (string) $content->getAttribute('locale'),
            'prompt_id' => $prompt?->id,
            'model' => $prompt !== null ? $prompt->model : (string) config('services.ai.model'),
            'provider' => $this->driver->provider(),
            'status' => GenerationStatus::Running,
            'attempts' => 1,
            'scheduled_at' => now(),
            'started_at' => now(),
        ]);

        if ($prompt === null) {
            $this->finalizeJob->execute($job, GenerationStatus::Failed, errorMessage: "Aucun prompt actif pour la transformation « {$mode->value} ».");

            return $job->fresh();
        }

        $userPrompt = strtr($prompt->user_template, [
            '{{body}}' => $currentBody,
            '{{facts}}' => $facts->map(fn (Fact $fact) => "- {$fact->key} : {$fact->value}")->implode("\n"),
        ]);

        $result = $this->driver->generate(new GenerationRequestData(
            systemPrompt: ForbiddenTopics::SYSTEM_GUARDRAIL."\n\n".$prompt->system_prompt,
            userPrompt: $userPrompt,
            model: $prompt->model,
        ));

        if (! $result->success) {
            $this->logAttempt->execute($job, $userPrompt, $facts, null, null);
            $this->finalizeJob->execute($job, GenerationStatus::Failed, $result, $result->errorMessage);

            return $job->fresh();
        }

        $report = $this->validator->execute((string) $result->rawOutput, $facts);
        $this->logAttempt->execute($job, $userPrompt, $facts, $result->rawOutput, $report->toArray());

        if ($report->insufficientData) {
            $this->finalizeJob->execute($job, GenerationStatus::Rejected, $result, 'Le modèle a signalé des faits insuffisants pour transformer ce contenu.');

            return $job->fresh();
        }

        $this->snapshotRevision->execute($content);

        // Toujours Review, y compris sur un contrôle propre : une
        // transformation n'est jamais auto-publiée, contrairement à une
        // création initiale qui peut atterrir en `Generated`.
        $content->update([
            'body' => $result->rawOutput,
            'status' => ContentStatus::Review,
            'generation_id' => $job->id,
            'published_at' => null,
        ]);

        $this->finalizeJob->execute($job, GenerationStatus::NeedsReview, $result, $report->passed ? null : implode(' ; ', $report->issues));

        return $job->fresh();
    }
}
