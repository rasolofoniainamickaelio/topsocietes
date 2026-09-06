<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\GenerateCityContentAction;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiGenerationLog;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\ContentRevision;
use App\Domain\Content\Models\ContentSourceLink;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->country = Country::factory()->create();
    $this->city = City::factory()->for($this->country)->create(['name' => 'Lyon']);
    $this->prompt = AiPrompt::factory()->create([
        'key' => 'city_content',
        'scope' => ContentSectionScope::City,
        'is_active' => true,
        'system_prompt' => 'Ne jamais inventer.',
        'user_template' => "Ville : {{city}}\nSection : {{section}}\nFaits :\n{{facts}}",
    ]);
});

it('rejects immediately without calling the provider when there are no usable facts', function (): void {
    Http::fake();

    $job = app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    expect($job->status)->toBe(GenerationStatus::Rejected);
    Http::assertNothingSent();
    expect(CityContent::query()->where('city_id', $this->city->id)->exists())->toBeFalse();
});

it('creates a Generated city content and source links on a clean pass', function (): void {
    $fact = Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Lyon compte 500716 habitants.']]],
            'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
        ], 200),
    ]);

    $job = app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    expect($job->status)->toBe(GenerationStatus::Succeeded);

    $content = CityContent::query()->where('city_id', $this->city->id)->where('section', 'history')->firstOrFail();
    expect($content->status)->toBe(ContentStatus::Generated)
        ->and($content->body)->toBe('Lyon compte 500716 habitants.')
        ->and($content->generation_id)->toBe($job->id);

    expect(ContentSourceLink::query()->where('content_type', $content->getMorphClass())->where('content_id', $content->id)->where('fact_id', $fact->id)->exists())->toBeTrue();

    $log = AiGenerationLog::query()->where('generation_job_id', $job->id)->firstOrFail();
    expect($log->raw_output)->toBe('Lyon compte 500716 habitants.');
});

it('flags a suspicious output as NeedsReview and stores it under Review status', function (): void {
    Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => "Cette ville jouit d'une excellente réputation."]]],
            'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
        ], 200),
    ]);

    $job = app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    expect($job->status)->toBe(GenerationStatus::NeedsReview)
        ->and($job->error_message)->toContain('réputation');

    $content = CityContent::query()->where('city_id', $this->city->id)->where('section', 'history')->firstOrFail();
    expect($content->status)->toBe(ContentStatus::Review);
});

it('rejects when the model declines with INSUFFICIENT_DATA, writing no content', function (): void {
    Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'INSUFFICIENT_DATA']]],
            'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 5],
        ], 200),
    ]);

    $job = app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    expect($job->status)->toBe(GenerationStatus::Rejected);
    expect(CityContent::query()->where('city_id', $this->city->id)->exists())->toBeFalse();
});

it('snapshots the previous content as a revision before overwriting it, but not on first generation', function (): void {
    Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);

    // Deux réponses distinctes pour le même endpoint : une séquence, jamais
    // deux `Http::fake()` — le second écraserait/empilerait sur le premier
    // stub plutôt que de le remplacer pour le second appel.
    Http::fakeSequence('*/chat/completions')
        ->push([
            'choices' => [['message' => ['content' => 'Lyon compte 500716 habitants.']]],
            'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
        ], 200)
        ->push([
            'choices' => [['message' => ['content' => 'Lyon compte 500716 habitants, texte mis à jour.']]],
            'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
        ], 200);

    app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    expect(ContentRevision::query()->count())->toBe(0);

    app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    $content = CityContent::query()->where('city_id', $this->city->id)->where('section', 'history')->firstOrFail();
    $revision = ContentRevision::query()->where('content_type', $content->getMorphClass())->where('content_id', $content->id)->firstOrFail();

    expect($revision->body)->toBe('Lyon compte 500716 habitants.')
        ->and($content->body)->toBe('Lyon compte 500716 habitants, texte mis à jour.');
});

it('marks the job Failed on a provider error, without throwing', function (): void {
    Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);

    Http::fake(['*/chat/completions' => Http::response(['error' => 'server error'], 500)]);

    $job = app(GenerateCityContentAction::class)->execute($this->city, ContentSection::History);

    expect($job->status)->toBe(GenerationStatus::Failed);
});
