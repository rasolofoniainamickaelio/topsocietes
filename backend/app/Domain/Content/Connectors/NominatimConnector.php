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
 * `/search` de Nominatim (OpenStreetMap) : collecte des faits de lieu
 * (type OSM, libellé normalisé) — jamais utilisé pour géocoder une
 * entreprise (décision de source de géocodage laissée ouverte en
 * Phase 04, volontairement distincte de cette collecte de faits).
 *
 * La politique d'usage Nominatim impose 1 requête/seconde maximum — un
 * `usleep` suffit à cette échelle (quelques villes), pas de file dédiée.
 */
class NominatimConnector implements SourceConnector
{
    private const MIN_INTERVAL_MICROSECONDS = 1_100_000;

    public function provider(): SourceProvider
    {
        return SourceProvider::OpenStreetMap;
    }

    public function collect(Model $subject): ?CollectedDocumentData
    {
        if (! $subject instanceof City) {
            return null;
        }

        $baseUrl = (string) config('services.sources.nominatim.base_url');
        $query = "{$subject->name}, {$subject->country->name}";
        $url = "{$baseUrl}/search?".http_build_query(['q' => $query, 'format' => 'json', 'limit' => 1]);

        usleep(self::MIN_INTERVAL_MICROSECONDS);

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

        /** @var array<int, array<string, mixed>> $raw */
        $raw = $response->json() ?? [];
        $match = $raw[0] ?? null;

        if ($match === null) {
            return null;
        }

        return new CollectedDocumentData(
            url: $url,
            rawPayload: $match,
            normalizedPayload: [
                'display_name' => $match['display_name'] ?? null,
                'osm_type' => $match['type'] ?? null,
            ],
            httpStatus: $response->status(),
            fetchedAt: CarbonImmutable::now(),
        );
    }

    public function extractFacts(CollectedDocumentData $document, Model $subject): array
    {
        $facts = [];

        if (is_string($document->normalizedPayload['display_name'] ?? null)) {
            $facts[] = new FactData(key: 'osm_display_name', value: $document->normalizedPayload['display_name']);
        }

        if (is_string($document->normalizedPayload['osm_type'] ?? null)) {
            $facts[] = new FactData(key: 'osm_type', value: $document->normalizedPayload['osm_type']);
        }

        return $facts;
    }
}
