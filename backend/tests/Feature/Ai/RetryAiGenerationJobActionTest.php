<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\RetryAiGenerationJobAction;
use App\Domain\Ai\Enums\GenerationMode;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Jobs\GenerateActivityContentJob;
use App\Domain\Ai\Jobs\GenerateCityActivityContentJob;
use App\Domain\Ai\Jobs\GenerateCityContentJob;
use App\Domain\Ai\Jobs\TransformContentJob;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Models\CityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Facades\Queue;

it('refuses to retry a job that is not in Failed status', function (): void {
    $job = AiGenerationJob::factory()->create(['status' => GenerationStatus::Rejected]);

    app(RetryAiGenerationJobAction::class)->execute($job);
})->throws(InvalidArgumentException::class);

it('re-dispatches a plain city generation', function (): void {
    Queue::fake();

    $city = City::factory()->create();
    $job = AiGenerationJob::factory()->create([
        'target_type' => $city->getMorphClass(),
        'target_id' => $city->id,
        'section' => ContentSection::History->value,
        'mode' => GenerationMode::Create,
        'status' => GenerationStatus::Failed,
        'attempts' => 1,
    ]);

    app(RetryAiGenerationJobAction::class)->execute($job);

    Queue::assertPushed(GenerateCityContentJob::class, fn (GenerateCityContentJob $dispatched): bool => $dispatched->city->is($city) && $dispatched->section === ContentSection::History);
    expect($job->fresh()->attempts)->toBe(2);
});

it('re-dispatches a city x activity generation using the stored activity_id', function (): void {
    Queue::fake();

    $city = City::factory()->create();
    $activity = Activity::factory()->create();
    $job = AiGenerationJob::factory()->create([
        'target_type' => $city->getMorphClass(),
        'target_id' => $city->id,
        'activity_id' => $activity->id,
        'sector_id' => null,
        'section' => ContentSection::LocalOverview->value,
        'mode' => GenerationMode::Create,
        'status' => GenerationStatus::Failed,
    ]);

    app(RetryAiGenerationJobAction::class)->execute($job);

    Queue::assertPushed(GenerateCityActivityContentJob::class, fn (GenerateCityActivityContentJob $dispatched): bool => $dispatched->city->is($city) && $dispatched->subject->is($activity));
});

it('re-dispatches a sector generation with no country (unrecoverable context)', function (): void {
    Queue::fake();

    $sector = Sector::factory()->create();
    $job = AiGenerationJob::factory()->create([
        'target_type' => $sector->getMorphClass(),
        'target_id' => $sector->id,
        'section' => ContentSection::HowItWorks->value,
        'mode' => GenerationMode::Create,
        'status' => GenerationStatus::Failed,
    ]);

    app(RetryAiGenerationJobAction::class)->execute($job);

    Queue::assertPushed(GenerateActivityContentJob::class, fn (GenerateActivityContentJob $dispatched): bool => $dispatched->subject->is($sector) && $dispatched->country === null);
});

it('re-dispatches a transform job against the existing content row', function (): void {
    Queue::fake();

    $content = CityContent::factory()->create();
    $job = AiGenerationJob::factory()->create([
        'target_type' => $content->getMorphClass(),
        'target_id' => $content->id,
        'section' => $content->section,
        'mode' => GenerationMode::Rewrite,
        'status' => GenerationStatus::Failed,
    ]);

    app(RetryAiGenerationJobAction::class)->execute($job);

    Queue::assertPushed(TransformContentJob::class, fn (TransformContentJob $dispatched): bool => $dispatched->content->is($content) && $dispatched->mode === GenerationMode::Rewrite);
});
