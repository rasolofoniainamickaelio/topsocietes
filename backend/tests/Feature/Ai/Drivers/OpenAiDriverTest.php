<?php

declare(strict_types=1);

use App\Domain\Ai\Data\GenerationRequestData;
use App\Domain\Ai\Drivers\OpenAiDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('returns a successful result with tokens and estimated cost', function (): void {
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => "Lyon est une ville du centre-est de la France.\n"]]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40],
        ], 200),
    ]);

    $result = app(OpenAiDriver::class)->generate(new GenerationRequestData(
        systemPrompt: 'Tu es un rédacteur.',
        userPrompt: 'Décris Lyon.',
        model: 'gpt-4o-mini',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->rawOutput)->toBe('Lyon est une ville du centre-est de la France.')
        ->and($result->inputTokens)->toBe(120)
        ->and($result->outputTokens)->toBe(40)
        ->and($result->costCents)->toBeInt();
});

it('fails cleanly on a non-2xx response, without throwing', function (): void {
    Http::fake(['*/chat/completions' => Http::response(['error' => 'rate limited'], 429)]);

    $result = app(OpenAiDriver::class)->generate(new GenerationRequestData(
        systemPrompt: 'Tu es un rédacteur.',
        userPrompt: 'Décris Lyon.',
        model: 'gpt-4o-mini',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('429');
});

it('fails cleanly on a connection failure, without throwing', function (): void {
    Http::fake(['*/chat/completions' => fn () => throw new ConnectionException('timed out')]);

    $result = app(OpenAiDriver::class)->generate(new GenerationRequestData(
        systemPrompt: 'Tu es un rédacteur.',
        userPrompt: 'Décris Lyon.',
        model: 'gpt-4o-mini',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('Connexion');
});

it('fails cleanly when the response has no message content', function (): void {
    Http::fake(['*/chat/completions' => Http::response(['choices' => []], 200)]);

    $result = app(OpenAiDriver::class)->generate(new GenerationRequestData(
        systemPrompt: 'Tu es un rédacteur.',
        userPrompt: 'Décris Lyon.',
        model: 'gpt-4o-mini',
    ));

    expect($result->success)->toBeFalse();
});
