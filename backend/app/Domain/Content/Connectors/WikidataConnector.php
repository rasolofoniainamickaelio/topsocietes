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
 * `Special:EntityData/{id}.json` : entité complète conservée telle quelle
 * en brut ; le normalisé ne retient que les "claims" (propriétés Wikidata)
 * réellement exploités — population (P1082) et superficie en km² (P2046).
 */
class WikidataConnector implements SourceConnector
{
    private const PROPERTY_POPULATION = 'P1082';

    private const PROPERTY_AREA_KM2 = 'P2046';

    public function provider(): SourceProvider
    {
        return SourceProvider::Wikidata;
    }

    public function collect(Model $subject): ?CollectedDocumentData
    {
        if (! $subject instanceof City || blank($subject->wikidata_id)) {
            return null;
        }

        $baseUrl = (string) config('services.sources.wikidata.base_url');
        $url = "{$baseUrl}/wiki/Special:EntityData/{$subject->wikidata_id}.json";

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
        $claims = $raw['entities'][$subject->wikidata_id]['claims'] ?? [];

        return new CollectedDocumentData(
            url: $url,
            rawPayload: $raw,
            normalizedPayload: [
                'population' => $this->extractQuantity($claims, self::PROPERTY_POPULATION),
                'area_km2' => $this->extractQuantity($claims, self::PROPERTY_AREA_KM2),
            ],
            httpStatus: $response->status(),
            fetchedAt: CarbonImmutable::now(),
        );
    }

    public function extractFacts(CollectedDocumentData $document, Model $subject): array
    {
        $facts = [];

        if ($document->normalizedPayload['population'] !== null) {
            $facts[] = new FactData(key: 'population', value: (string) $document->normalizedPayload['population']);
        }

        if ($document->normalizedPayload['area_km2'] !== null) {
            $facts[] = new FactData(key: 'area_km2', value: (string) $document->normalizedPayload['area_km2']);
        }

        return $facts;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function extractQuantity(array $claims, string $property): float|int|null
    {
        $amount = $claims[$property][0]['mainsnak']['datavalue']['value']['amount'] ?? null;

        if (! is_string($amount)) {
            return null;
        }

        // Wikidata préfixe les quantités positives d'un "+" (ex. "+500716").
        return str_contains($amount, '.') ? (float) $amount : (int) $amount;
    }
}
