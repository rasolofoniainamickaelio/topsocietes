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
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\ContentSourceLink;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Collection;

/**
 * Génère un bloc `city_activity_contents` (le seul contenu réellement
 * croisé, Phase 08) : ville + Activity OU Sector — jamais les deux. Les
 * faits combinent ceux de la ville et ceux du sujet métier, seule façon
 * d'obtenir un contenu qui parle à la fois du territoire et de l'activité
 * sans jamais dupliquer l'un ou l'autre en base.
 */
class GenerateCityActivityContentAction
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

    public function execute(City $city, Activity|Sector $subject, ContentSection $section): AiGenerationJob
    {
        /** @var Collection<int, Fact> $facts */
        $facts = $city->facts()->usable()->get()->merge($subject->facts()->usable()->get());

        $prompt = AiPrompt::query()
            ->where('scope', ContentSectionScope::CityActivity)
            ->where('is_active', true)
            ->first();

        $job = AiGenerationJob::create([
            'target_type' => $city->getMorphClass(),
            'target_id' => $city->id,
            'activity_id' => $subject instanceof Activity ? $subject->id : null,
            'sector_id' => $subject instanceof Sector ? $subject->id : null,
            'section' => $section->value,
            'mode' => GenerationMode::Create,
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
            $this->finalizeJob->execute($job, GenerationStatus::Failed, errorMessage: 'Aucun prompt actif pour le scope city_activity.');

            return $job->fresh();
        }

        if ($facts->isEmpty()) {
            $this->logAttempt->execute($job, '', $facts, null, null);
            $this->finalizeJob->execute($job, GenerationStatus::Rejected, errorMessage: 'Aucun fait exploitable — aucun appel au fournisseur.');

            return $job->fresh();
        }

        if (! $this->checkBudget->execute($city->country)) {
            $this->finalizeJob->execute($job, GenerationStatus::Rejected, errorMessage: 'Budget IA mensuel du pays dépassé.');

            return $job->fresh();
        }

        $userPrompt = $this->buildUserPrompt($prompt->user_template, $city, $subject, $section, $facts);

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
            $this->writeContent($city, $subject, $section, (string) $result->rawOutput, ContentStatus::Review, $job, $facts);
            $this->finalizeJob->execute($job, GenerationStatus::NeedsReview, $result, implode(' ; ', $report->issues));

            return $job->fresh();
        }

        $this->writeContent($city, $subject, $section, (string) $result->rawOutput, ContentStatus::Generated, $job, $facts);
        $this->finalizeJob->execute($job, GenerationStatus::Succeeded, $result);

        return $job->fresh();
    }

    /**
     * @param  Collection<int, Fact>  $facts
     */
    private function buildUserPrompt(string $template, City $city, Activity|Sector $subject, ContentSection $section, Collection $facts): string
    {
        $label = $subject instanceof Activity ? $subject->public_label : $subject->name;

        $factsText = $facts
            ->map(fn (Fact $fact) => "- {$fact->key} : {$fact->value}")
            ->implode("\n");

        return strtr($template, [
            '{{city}}' => $city->name,
            '{{activity}}' => $label,
            '{{section}}' => $section->value,
            '{{facts}}' => $factsText,
        ]);
    }

    /**
     * @param  Collection<int, Fact>  $facts
     */
    private function writeContent(City $city, Activity|Sector $subject, ContentSection $section, string $body, ContentStatus $status, AiGenerationJob $job, Collection $facts): CityActivityContent
    {
        $key = [
            'city_id' => $city->id,
            'activity_id' => $subject instanceof Activity ? $subject->id : null,
            'sector_id' => $subject instanceof Sector ? $subject->id : null,
            'locale' => self::LOCALE,
            'section' => $section->value,
        ];

        $existing = CityActivityContent::query()->where($key)->first();

        if ($existing !== null) {
            AiWritableFields::assertWritable($existing);
            $this->snapshotRevision->execute($existing);
        }

        $content = CityActivityContent::updateOrCreate(
            $key,
            ['body' => $body, 'status' => $status, 'generation_id' => $job->id, 'published_at' => null],
        );

        AiWritableFields::assertWritable($content);

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
