<?php

declare(strict_types=1);

namespace App\Domain\Ai\Drivers;

use App\Domain\Ai\Contracts\AiDriver;
use App\Domain\Ai\Data\GenerationRequestData;
use App\Domain\Ai\Data\GenerationResultData;
use App\Domain\Ai\Enums\AiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Appelle `/chat/completions` (synchrone côté fournisseur, mais toujours
 * depuis un Job de queue — jamais pendant une requête HTTP publique,
 * CLAUDE.md §6.1). La vraie Batch API OpenAI (fichier différé, jusqu'à
 * 24h, moitié prix) reste une optimisation pour plus tard, quand le
 * volume la justifiera — cette implémentation reste sous
 * `AiProvider::OpenAiBatch` car c'est la valeur déjà configurée, pas
 * parce qu'elle utilise le vrai mécanisme de batch fichier du
 * fournisseur.
 */
class OpenAiDriver implements AiDriver
{
    public function provider(): AiProvider
    {
        return AiProvider::OpenAiBatch;
    }

    public function generate(GenerationRequestData $request): GenerationResultData
    {
        $baseUrl = (string) config('services.ai.base_url');
        $apiKey = (string) config('services.ai.api_key');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $request->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $request->systemPrompt],
                        ['role' => 'user', 'content' => $request->userPrompt],
                    ],
                ]);
        } catch (ConnectionException $e) {
            return new GenerationResultData(success: false, errorMessage: "Connexion échouée : {$e->getMessage()}");
        }

        if (! $response->successful()) {
            return new GenerationResultData(
                success: false,
                errorMessage: "Réponse HTTP {$response->status()} : ".$response->body(),
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            return new GenerationResultData(success: false, errorMessage: 'Réponse vide ou malformée du fournisseur.');
        }

        $inputTokens = (int) ($response->json('usage.prompt_tokens') ?? 0);
        $outputTokens = (int) ($response->json('usage.completion_tokens') ?? 0);

        return new GenerationResultData(
            success: true,
            rawOutput: trim($content),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            costCents: $this->estimateCostCents($inputTokens, $outputTokens),
        );
    }

    private function estimateCostCents(int $inputTokens, int $outputTokens): int
    {
        $inputRate = (float) config('services.ai.pricing_cents_per_1k.input');
        $outputRate = (float) config('services.ai.pricing_cents_per_1k.output');

        $cost = ($inputTokens / 1000) * $inputRate + ($outputTokens / 1000) * $outputRate;

        return (int) round($cost);
    }
}
