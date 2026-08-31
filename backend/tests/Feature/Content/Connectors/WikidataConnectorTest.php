<?php

declare(strict_types=1);

use App\Domain\Content\Connectors\WikidataConnector;
use App\Domain\Geo\Models\City;
use Illuminate\Support\Facades\Http;

it('collects population and area facts from claims', function (): void {
    Http::fake([
        '*/Special:EntityData/Q456.json' => Http::response([
            'entities' => [
                'Q456' => [
                    'claims' => [
                        'P1082' => [['mainsnak' => ['datavalue' => ['value' => ['amount' => '+500716']]]]],
                        'P2046' => [['mainsnak' => ['datavalue' => ['value' => ['amount' => '+47.87']]]]],
                    ],
                ],
            ],
        ], 200),
    ]);

    $city = City::factory()->create(['wikidata_id' => 'Q456']);

    $connector = new WikidataConnector;
    $document = $connector->collect($city);

    expect($document)->not->toBeNull()
        ->and($document->normalizedPayload['population'])->toBe(500716)
        ->and($document->normalizedPayload['area_km2'])->toBe(47.87);

    $facts = $connector->extractFacts($document, $city);

    expect($facts)->toHaveCount(2);
    expect(collect($facts)->firstWhere('key', 'population')->value)->toBe('500716');
    expect(collect($facts)->firstWhere('key', 'area_km2')->value)->toBe('47.87');
});

it('skips facts for missing claims', function (): void {
    Http::fake([
        '*/Special:EntityData/Q1.json' => Http::response([
            'entities' => ['Q1' => ['claims' => []]],
        ], 200),
    ]);

    $city = City::factory()->create(['wikidata_id' => 'Q1']);

    $connector = new WikidataConnector;
    $document = $connector->collect($city);
    $facts = $connector->extractFacts($document, $city);

    expect($facts)->toBeEmpty();
});

it('returns null when the city has no wikidata id', function (): void {
    $city = City::factory()->create(['wikidata_id' => null]);

    $document = (new WikidataConnector)->collect($city);

    expect($document)->toBeNull();
});
