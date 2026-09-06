<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\GenerateCityActivityContentAction;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->country = Country::factory()->create();
    $this->city = City::factory()->for($this->country)->create(['name' => 'Lyon']);

    AiPrompt::factory()->create([
        'key' => 'city_activity_content',
        'scope' => ContentSectionScope::CityActivity,
        'is_active' => true,
        'system_prompt' => 'Ne jamais inventer.',
        'user_template' => "Ville : {{city}}\nActivité : {{activity}}\nFaits :\n{{facts}}",
    ]);
});

it('rejects when neither the city nor the activity has any usable fact', function (): void {
    Http::fake();
    $activity = Activity::factory()->create();

    $job = app(GenerateCityActivityContentAction::class)->execute($this->city, $activity, ContentSection::LocalOverview);

    expect($job->status)->toBe(GenerationStatus::Rejected);
    Http::assertNothingSent();
});

it('merges city and activity facts, and writes a city_activity_contents row', function (): void {
    $activity = Activity::factory()->create(['public_label' => 'Plombier']);
    Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);
    Fact::factory()->for($activity, 'subject')->create(['key' => 'reglementation', 'value' => 'RGE obligatoire']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Lyon compte 500716 habitants et impose le RGE obligatoire.']]],
            'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 20],
        ], 200),
    ]);

    $job = app(GenerateCityActivityContentAction::class)->execute($this->city, $activity, ContentSection::LocalOverview);

    expect($job->status)->toBe(GenerationStatus::Succeeded);

    $content = CityActivityContent::query()
        ->where('city_id', $this->city->id)
        ->where('activity_id', $activity->id)
        ->firstOrFail();

    expect($content->status)->toBe(ContentStatus::Generated)
        ->and($content->sector_id)->toBeNull();
});

it('targets a sector instead of an activity for the cross content, mutually exclusive', function (): void {
    $sector = Sector::factory()->create();
    Fact::factory()->for($this->city, 'subject')->create(['key' => 'population', 'value' => '500716']);
    Fact::factory()->for($sector, 'subject')->create(['key' => 'note', 'value' => 'en croissance']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Lyon compte 500716 habitants.']]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 15],
        ], 200),
    ]);

    $job = app(GenerateCityActivityContentAction::class)->execute($this->city, $sector, ContentSection::LocalOverview);

    expect($job->status)->toBe(GenerationStatus::Succeeded);

    $content = CityActivityContent::query()->where('city_id', $this->city->id)->where('sector_id', $sector->id)->firstOrFail();
    expect($content->activity_id)->toBeNull();
});
