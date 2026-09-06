<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\TransformContentAction;
use App\Domain\Ai\Enums\GenerationMode;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\ContentRevision;
use App\Domain\Content\Models\ContentSourceLink;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\City;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->city = City::factory()->create(['name' => 'Lyon']);
    $this->fact = Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);

    $this->content = CityContent::factory()->for($this->city)->create([
        'section' => 'history',
        'body' => 'Lyon compte 500716 habitants.',
        'status' => ContentStatus::Published,
    ]);

    ContentSourceLink::create([
        'content_type' => $this->content->getMorphClass(),
        'content_id' => $this->content->id,
        'fact_id' => $this->fact->id,
    ]);

    AiPrompt::factory()->create([
        'key' => 'transform_rewrite',
        'is_active' => true,
        'system_prompt' => 'Ne jamais inventer.',
        'user_template' => "Texte : {{body}}\nFaits :\n{{facts}}",
    ]);
});

it('rejects the Create mode outright', function (): void {
    app(TransformContentAction::class)->execute($this->content, GenerationMode::Create);
})->throws(InvalidArgumentException::class);

it('rewrites an existing published content and always lands it in Review, never auto-published', function (): void {
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Lyon, ville de 500716 habitants, se distingue.']]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 15],
        ], 200),
    ]);

    $job = app(TransformContentAction::class)->execute($this->content, GenerationMode::Rewrite);

    expect($job->status)->toBe(GenerationStatus::NeedsReview)
        ->and($job->mode)->toBe(GenerationMode::Rewrite);

    $this->content->refresh();
    expect($this->content->status)->toBe(ContentStatus::Review)
        ->and($this->content->body)->toBe('Lyon, ville de 500716 habitants, se distingue.');

    $revision = ContentRevision::query()->where('content_id', $this->content->id)->firstOrFail();
    expect($revision->body)->toBe('Lyon compte 500716 habitants.');
});

it('rejects rewriting an empty content', function (): void {
    $this->content->update(['body' => '']);

    app(TransformContentAction::class)->execute($this->content, GenerationMode::Rewrite);
})->throws(InvalidArgumentException::class);

it('fails cleanly when no active prompt exists for the requested mode', function (): void {
    // Seul `transform_rewrite` a un prompt actif (beforeEach) — Summarize n'en a aucun.
    $job = app(TransformContentAction::class)->execute($this->content, GenerationMode::Summarize);

    expect($job->status)->toBe(GenerationStatus::Failed);
    $this->content->refresh();
    expect($this->content->status)->toBe(ContentStatus::Published);
});
