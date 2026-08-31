<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Contracts\AiDriver;
use App\Domain\Ai\Data\GenerationRequestData;
use App\Domain\Ai\Data\GenerationResultData;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Ai\Models\AiGenerationLog;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\ContentSourceLink;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\City;
use Illuminate\Support\Collection;

/**
 * Orchestre une génération de contenu communal de bout en bout (Phases
 * 10-11) : faits sourcés → prompt → fournisseur → contrôle
 * anti-hallucination → écriture. Ne publie jamais automatiquement — le
 * contenu généré atterrit en `ContentStatus::Generated` (propre) ou
 * `Review` (douteux), toujours en attente d'un humain (CLAUDE.md §6.1/§6.7).
 */
class GenerateCityContentAction
{
    private const LOCALE = 'fr';

    public function __construct(
        private readonly AiDriver $driver,
        private readonly ValidateGenerationOutputAction $validator,
    ) {}

    public function execute(City $city, ContentSection $section): AiGenerationJob
    {
        /** @var Collection<int, Fact> $facts */
        $facts = $city->facts()->usable()->get();

        $prompt = AiPrompt::query()
            ->where('scope', ContentSectionScope::City)
            ->where('is_active', true)
            ->first();

        $job = AiGenerationJob::create([
            'target_type' => $city->getMorphClass(),
            'target_id' => $city->id,
            'section' => $section->value,
            'locale' => self::LOCALE,
            'prompt_id' => $prompt?->id,
            'model' => $prompt !== null ? $prompt->model : (string) config('services.ai.model'),
            'provider' => $this->driver->provider(),
            'status' => GenerationStatus::Running,
            'attempts' => 1,
            'scheduled_at' => now(),
            'started_at' => now(),
        ]);

        if ($prompt === null) {
            $this->finalize($job, GenerationStatus::Failed, errorMessage: 'Aucun prompt actif pour le scope city.');

            return $job->fresh();
        }

        if ($facts->isEmpty()) {
            $this->log($job, '', $facts, null, null);
            $this->finalize($job, GenerationStatus::Rejected, errorMessage: 'Aucun fait exploitable pour cette ville — aucun appel au fournisseur.');

            return $job->fresh();
        }

        $userPrompt = $this->buildUserPrompt($prompt->user_template, $city, $section, $facts);

        $result = $this->driver->generate(new GenerationRequestData(
            systemPrompt: $prompt->system_prompt,
            userPrompt: $userPrompt,
            model: $prompt->model,
        ));

        if (! $result->success) {
            $this->log($job, $userPrompt, $facts, null, null);
            $this->finalize($job, GenerationStatus::Failed, $result, $result->errorMessage);

            return $job->fresh();
        }

        $report = $this->validator->execute((string) $result->rawOutput, $facts);
        $this->log($job, $userPrompt, $facts, $result->rawOutput, $report->toArray());

        if ($report->insufficientData) {
            $this->finalize($job, GenerationStatus::Rejected, $result, 'Le modèle a signalé des faits insuffisants pour rédiger ce contenu.');

            return $job->fresh();
        }

        if (! $report->passed) {
            $this->writeContent($city, $section, (string) $result->rawOutput, ContentStatus::Review, $job, $facts);
            $this->finalize($job, GenerationStatus::NeedsReview, $result, implode(' ; ', $report->issues));

            return $job->fresh();
        }

        $this->writeContent($city, $section, (string) $result->rawOutput, ContentStatus::Generated, $job, $facts);
        $this->finalize($job, GenerationStatus::Succeeded, $result);

        return $job->fresh();
    }

    /**
     * @param  Collection<int, Fact>  $facts
     */
    private function buildUserPrompt(string $template, City $city, ContentSection $section, Collection $facts): string
    {
        $factsText = $facts
            ->map(fn (Fact $fact) => "- {$fact->key} : {$fact->value}")
            ->implode("\n");

        return strtr($template, [
            '{{city}}' => $city->name,
            '{{section}}' => $section->value,
            '{{facts}}' => $factsText,
        ]);
    }

    /**
     * @param  Collection<int, Fact>  $facts
     * @param  array<string, mixed>|null  $validationReport
     */
    private function log(AiGenerationJob $job, string $userPrompt, Collection $facts, ?string $rawOutput, ?array $validationReport): void
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

    private function finalize(AiGenerationJob $job, GenerationStatus $status, ?GenerationResultData $result = null, ?string $errorMessage = null): void
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

    /**
     * @param  Collection<int, Fact>  $facts
     */
    private function writeContent(City $city, ContentSection $section, string $body, ContentStatus $status, AiGenerationJob $job, Collection $facts): CityContent
    {
        $content = CityContent::updateOrCreate(
            ['city_id' => $city->id, 'locale' => self::LOCALE, 'section' => $section->value],
            ['body' => $body, 'status' => $status, 'generation_id' => $job->id, 'published_at' => null],
        );

        // Repartir d'une trace propre à chaque (re)génération plutôt que
        // d'accumuler des liens vers des faits d'une précédente exécution.
        ContentSourceLink::query()
            ->where('content_type', $content->getMorphClass())
            ->where('content_id', $content->id)
            ->delete();

        foreach ($facts as $fact) {
            ContentSourceLink::create([
                'content_type' => $content->getMorphClass(),
                'content_id' => $content->id,
                'fact_id' => $fact->id,
                'source_document_id' => null,
            ]);
        }

        return $content;
    }
}
