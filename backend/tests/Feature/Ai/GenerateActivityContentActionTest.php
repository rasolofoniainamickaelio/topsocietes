<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\GenerateActivityContentAction;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiBudget;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    AiPrompt::factory()->create([
        'key' => 'activity_content',
        'scope' => ContentSectionScope::Activity,
        'is_active' => true,
        'system_prompt' => 'Ne jamais inventer.',
        'user_template' => "Activité : {{activity}}\nSection : {{section}}\nFaits :\n{{facts}}",
    ]);
});

it('rejects immediately without calling the provider when the activity has no usable facts', function (): void {
    Http::fake();
    $activity = Activity::factory()->create();

    $job = app(GenerateActivityContentAction::class)->execute($activity, null, ContentSection::UnderstandingSector);

    expect($job->status)->toBe(GenerationStatus::Rejected);
    Http::assertNothingSent();
});

it('creates a generic activity content on a clean pass', function (): void {
    $activity = Activity::factory()->create(['public_label' => 'Plomberie']);
    Fact::factory()->for($activity, 'subject')->create(['key' => 'reglementation', 'value' => 'RGE obligatoire']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'La plomberie est un métier réglementé (RGE obligatoire).']]],
            'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 15],
        ], 200),
    ]);

    $job = app(GenerateActivityContentAction::class)->execute($activity, null, ContentSection::UnderstandingSector);

    expect($job->status)->toBe(GenerationStatus::Succeeded);

    $content = ActivityContent::query()->where('activity_id', $activity->id)->whereNull('country_id')->firstOrFail();
    expect($content->status)->toBe(ContentStatus::Generated)
        ->and($content->sector_id)->toBeNull();
});

it('generates content for a sector instead of an activity, mutually exclusive', function (): void {
    $sector = Sector::factory()->create();
    Fact::factory()->for($sector, 'subject')->create(['key' => 'note', 'value' => 'secteur en croissance']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Un secteur en croissance.']]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 10],
        ], 200),
    ]);

    $job = app(GenerateActivityContentAction::class)->execute($sector, null, ContentSection::UnderstandingSector);

    expect($job->status)->toBe(GenerationStatus::Succeeded);

    $content = ActivityContent::query()->where('sector_id', $sector->id)->firstOrFail();
    expect($content->activity_id)->toBeNull();
});

it('scopes a country-specific content separately from the generic one', function (): void {
    $activity = Activity::factory()->create();
    $country = Country::factory()->create();
    Fact::factory()->for($activity, 'subject')->create(['key' => 'note', 'value' => 'fait partagé']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Contenu spécifique au pays.']]],
            'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 10],
        ], 200),
    ]);

    app(GenerateActivityContentAction::class)->execute($activity, $country, ContentSection::UnderstandingSector);

    $generic = ActivityContent::query()->where('activity_id', $activity->id)->whereNull('country_id')->exists();
    $specific = ActivityContent::query()->where('activity_id', $activity->id)->where('country_id', $country->id)->exists();

    expect($generic)->toBeFalse()
        ->and($specific)->toBeTrue();
});

it('rejects when the monthly AI budget for the country is exhausted', function (): void {
    $activity = Activity::factory()->create();
    $country = Country::factory()->create();
    Fact::factory()->for($activity, 'subject')->create(['key' => 'note', 'value' => 'fait']);

    AiBudget::factory()->create([
        'country_id' => $country->id,
        'period' => now()->format('Y-m'),
        'max_cost_cents' => 100,
        'spent_cents' => 100,
    ]);

    Http::fake();

    $job = app(GenerateActivityContentAction::class)->execute($activity, $country, ContentSection::UnderstandingSector);

    expect($job->status)->toBe(GenerationStatus::Rejected);
    Http::assertNothingSent();
});
