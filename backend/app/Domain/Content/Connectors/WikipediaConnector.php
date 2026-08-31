<?php

declare(strict_types=1);

namespace App\Domain\Content\Connectors;

use App\Domain\Content\Contracts\SourceConnector;
use App\Domain\Content\Data\CollectedDocumentData;
use App\Domain\Content\Data\FactData;
use App\Domain\Content\Enums\SourceProvider;
use App\Domain\Geo\Models\City;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * API REST Wikipedia (`/page/summary/{title}`) : renvoie déjà un extrait
 * en texte brut (`extract`), sans balisage HTML à nettoyer — ce qui évite
 * toute dépendance de parsing HTML pour cette source.
 */
class WikipediaConnector implements SourceConnector
{
    public function provider(): SourceProvider
    {
        return SourceProvider::Wikipedia;
    }

    public function collect(Model $subject): ?CollectedDocumentData
    {
        if (! $subject instanceof City || blank($subject->wikipedia_title)) {
            return null;
        }

        $baseUrl = (string) config('services.sources.wikipedia.base_url');
        $url = $baseUrl.'/api/rest_v1/page/summary/'.rawurlencode($subject->wikipedia_title);

        try {
            $response = Http::withHeaders(['User-Agent' => config('services.sources.user_agent')])
                ->timeout(10)
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $raw */
        $raw = $response->json() ?? [];
        $extract = is_string($raw['extract'] ?? null) ? trim($raw['extract']) : '';

        return new CollectedDocumentData(
            url: $url,
            rawPayload: $raw,
            normalizedPayload: ['extract' => $extract, 'title' => $raw['title'] ?? $subject->wikipedia_title],
            httpStatus: $response->status(),
            fetchedAt: CarbonImmutable::now(),
        );
    }

    public function extractFacts(CollectedDocumentData $document, Model $subject): array
    {
        $extract = (string) ($document->normalizedPayload['extract'] ?? '');

        if ($extract === '') {
            return [];
        }

        return [new FactData(key: 'wikipedia_summary', value: $extract)];
    }
}
