<?php

declare(strict_types=1);

use App\Domain\Content\Actions\CollectCityFactsAction;
use App\Domain\Content\Enums\SourceProvider;
use App\Domain\Content\Models\Fact;
use App\Domain\Content\Models\Source;
use App\Domain\Content\Models\SourceDocument;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Enums\JobRunStatus;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Source::factory()->create(['provider' => SourceProvider::Wikipedia, 'reliability_score' => 70, 'is_active' => true]);
    Source::factory()->create(['provider' => SourceProvider::Wikidata, 'reliability_score' => 80, 'is_active' => true]);
    Source::factory()->create(['provider' => SourceProvider::OpenStreetMap, 'reliability_score' => 60, 'is_active' => true]);
});

it('collects documents and facts from every available connector and logs a completed job run', function (): void {
    Http::fake([
        '*/api/rest_v1/page/summary/*' => Http::response(['title' => 'Lyon', 'extract' => 'Résumé de Lyon.'], 200),
        '*/Special:EntityData/*' => Http::response([
            'entities' => ['Q456' => ['claims' => [
                'P1082' => [['mainsnak' => ['datavalue' => ['value' => ['amount' => '+500716']]]]],
            ]]],
        ], 200),
        '*nominatim.openstreetmap.org/search*' => Http::response([
            ['display_name' => 'Lyon, France', 'type' => 'administrative'],
        ], 200),
    ]);

    $country = Country::factory()->create(['name' => 'France']);
    $city = City::factory()->for($country)->create([
        'name' => 'Lyon',
        'wikipedia_title' => 'Lyon',
        'wikidata_id' => 'Q456',
    ]);

    $jobRun = app(CollectCityFactsAction::class)->execute($city);

    expect($jobRun->status)->toBe(JobRunStatus::Completed)
        ->and(SourceDocument::query()->where('subject_type', $city->getMorphClass())->where('subject_id', $city->id)->count())->toBe(3)
        ->and(Fact::query()->where('subject_type', $city->getMorphClass())->where('subject_id', $city->id)->where('key', 'wikipedia_summary')->exists())->toBeTrue()
        ->and(Fact::query()->where('subject_type', $city->getMorphClass())->where('subject_id', $city->id)->where('key', 'population')->exists())->toBeTrue();
});

it('does not let one failing source block the others', function (): void {
    Http::fake([
        '*/api/rest_v1/page/summary/*' => Http::response(null, 500),
        '*/Special:EntityData/*' => Http::response([
            'entities' => ['Q456' => ['claims' => [
                'P1082' => [['mainsnak' => ['datavalue' => ['value' => ['amount' => '+500716']]]]],
            ]]],
        ], 200),
        '*nominatim.openstreetmap.org/search*' => Http::response([], 200),
    ]);

    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create(['wikipedia_title' => 'Lyon', 'wikidata_id' => 'Q456']);

    $jobRun = app(CollectCityFactsAction::class)->execute($city);

    expect($jobRun->status)->toBe(JobRunStatus::Completed)
        ->and(Fact::query()->where('subject_id', $city->id)->where('key', 'population')->exists())->toBeTrue()
        ->and(Fact::query()->where('subject_id', $city->id)->where('key', 'wikipedia_summary')->exists())->toBeFalse();
});
