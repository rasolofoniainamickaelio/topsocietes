<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Contracts\AiDriver;
use App\Domain\Ai\Data\GenerationRequestData;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Ai\Support\AiWritableFields;
use App\Domain\Ai\Support\ForbiddenTopics;
use App\Domain\Content\Actions\SnapshotContentRevisionAction;
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
        private readonly SnapshotContentRevisionAction $snapshotRevision,
        private readonly LogGenerationAttemptAction $logAttempt,
        private readonly FinalizeGenerationJobAction $finalizeJob,
        private readonly CheckAiBudgetAction $checkBudget,
        private readonly RecordAiSpendAction $recordSpend,
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
            $this->finalizeJob->execute($job, GenerationStatus::Failed, errorMessage: 'Aucun prompt actif pour le scope city.');

            return $job->fresh();
        }

        if ($facts->isEmpty()) {
            $this->logAttempt->execute($job, '', $facts, null, null);
            $this->finalizeJob->execute($job, GenerationStatus::Rejected, errorMessage: 'Aucun fait exploitable pour cette ville — aucun appel au fournisseur.');

            return $job->fresh();
        }

        if (! $this->checkBudget->execute($city->country)) {
            $this->finalizeJob->execute($job, GenerationStatus::Rejected, errorMessage: 'Budget IA mensuel du pays dépassé.');

            return $job->fresh();
        }

        $userPrompt = $this->buildUserPrompt($prompt->user_template, $city, $section, $facts);

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

        $this->recordSpend->execute($city->country, $result->costCents ?? 0);

        $report = $this->validator->execute((string) $result->rawOutput, $facts);
        $this->logAttempt->execute($job, $userPrompt, $facts, $result->rawOutput, $report->toArray());

        if ($report->insufficientData) {
            $this->finalizeJob->execute($job, GenerationStatus::Rejected, $result, 'Le modèle a signalé des faits insuffisants pour rédiger ce contenu.');

            return $job->fresh();
        }

        if (! $report->passed) {
            $this->writeContent($city, $section, (string) $result->rawOutput, ContentStatus::Review, $job, $facts);
            $this->finalizeJob->execute($job, GenerationStatus::NeedsReview, $result, implode(' ; ', $report->issues));

            return $job->fresh();
        }

        $this->writeContent($city, $section, (string) $result->rawOutput, ContentStatus::Generated, $job, $facts);
        $this->finalizeJob->execute($job, GenerationStatus::Succeeded, $result);

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
     */
    private function writeContent(City $city, ContentSection $section, string $body, ContentStatus $status, AiGenerationJob $job, Collection $facts): CityContent
    {
        $existing = CityContent::query()
            ->where(['city_id' => $city->id, 'locale' => self::LOCALE, 'section' => $section->value])
            ->first();

        if ($existing !== null) {
            AiWritableFields::assertWritable($existing);
            $this->snapshotRevision->execute($existing);
        }

        $content = CityContent::updateOrCreate(
            ['city_id' => $city->id, 'locale' => self::LOCALE, 'section' => $section->value],
            ['body' => $body, 'status' => $status, 'generation_id' => $job->id, 'published_at' => null],
        );

        AiWritableFields::assertWritable($content);

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
