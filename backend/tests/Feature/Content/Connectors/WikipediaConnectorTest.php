<?php

declare(strict_types=1);

use App\Domain\Content\Connectors\WikipediaConnector;
use App\Domain\Geo\Models\City;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('collects a document and extracts a summary fact', function (): void {
    Http::fake([
        '*/api/rest_v1/page/summary/*' => Http::response([
            'title' => 'Lyon',
            'extract' => 'Lyon est une commune du centre-est de la France.',
        ], 200),
    ]);

    $city = City::factory()->create(['wikipedia_title' => 'Lyon']);

    $connector = new WikipediaConnector;
    $document = $connector->collect($city);

    expect($document)->not->toBeNull()
        ->and($document->normalizedPayload['extract'])->toBe('Lyon est une commune du centre-est de la France.');

    $facts = $connector->extractFacts($document, $city);

    expect($facts)->toHaveCount(1)
        ->and($facts[0]->key)->toBe('wikipedia_summary')
        ->and($facts[0]->value)->toBe('Lyon est une commune du centre-est de la France.');
});

it('returns null when the city has no wikipedia title', function (): void {
    $city = City::factory()->create(['wikipedia_title' => null]);

    $document = (new WikipediaConnector)->collect($city);

    expect($document)->toBeNull();
});

it('returns null on a non-2xx response', function (): void {
    Http::fake([
        '*/api/rest_v1/page/summary/*' => Http::response(null, 404),
    ]);

    $city = City::factory()->create(['wikipedia_title' => 'Ville-Inconnue']);

    $document = (new WikipediaConnector)->collect($city);

    expect($document)->toBeNull();
});

it('returns null on a connection failure', function (): void {
    Http::fake([
        '*/api/rest_v1/page/summary/*' => fn () => throw new ConnectionException('timeout'),
    ]);

    $city = City::factory()->create(['wikipedia_title' => 'Lyon']);

    $document = (new WikipediaConnector)->collect($city);

    expect($document)->toBeNull();
});
