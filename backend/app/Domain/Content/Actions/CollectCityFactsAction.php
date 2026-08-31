<?php

declare(strict_types=1);

namespace App\Domain\Content\Actions;

use App\Domain\Content\Connectors\NominatimConnector;
use App\Domain\Content\Connectors\WikidataConnector;
use App\Domain\Content\Connectors\WikipediaConnector;
use App\Domain\Content\Contracts\SourceConnector;
use App\Domain\Content\Models\Fact;
use App\Domain\Content\Models\Source;
use App\Domain\Content\Models\SourceDocument;
use App\Domain\Geo\Models\City;
use App\Enums\JobRunStatus;
use App\Models\JobRun;
use Throwable;

/**
 * Orchestre les connecteurs actifs pour une ville (Phase 09) : une source
 * en échec ou indisponible n'empêche jamais les autres de s'exécuter
 * ("gestion des erreurs et des sources indisponibles"). Le déroulé complet
 * est journalisé dans un `JobRun`, jusqu'ici scaffoldé mais inutilisé.
 */
class CollectCityFactsAction
{
    public function __construct(
        private readonly WikipediaConnector $wikipediaConnector,
        private readonly WikidataConnector $wikidataConnector,
        private readonly NominatimConnector $nominatimConnector,
    ) {}

    public function execute(City $city): JobRun
    {
        $jobRun = JobRun::create([
            'name' => 'sources:collect-city',
            'status' => JobRunStatus::Running,
            'payload' => ['city_id' => $city->id, 'city_slug' => $city->slug],
            'attempts' => 1,
            'started_at' => now(),
        ]);

        $output = [];
        $hasFailure = false;

        foreach ($this->connectors() as $connector) {
            $key = $connector->provider()->value;

            try {
                $output[$key] = $this->collectFrom($connector, $city);
            } catch (Throwable $e) {
                $hasFailure = true;
                $output[$key] = "erreur : {$e->getMessage()}";
            }
        }

        $jobRun->update([
            'status' => $hasFailure ? JobRunStatus::Failed : JobRunStatus::Completed,
            'output' => $output,
            'finished_at' => now(),
            'duration_ms' => (int) $jobRun->started_at->diffInMilliseconds(now()),
        ]);

        return $jobRun;
    }

    /** @return array<int, SourceConnector> */
    private function connectors(): array
    {
        return [$this->wikipediaConnector, $this->wikidataConnector, $this->nominatimConnector];
    }

    private function collectFrom(SourceConnector $connector, City $city): string
    {
        $source = Source::query()
            ->where('provider', $connector->provider())
            ->where('is_active', true)
            ->first();

        if ($source === null) {
            return 'source inactive ou non configurée';
        }

        $document = $connector->collect($city);

        if ($document === null) {
            return 'aucune donnée (identifiant manquant ou source indisponible)';
        }

        SourceDocument::create([
            'source_id' => $source->id,
            'subject_type' => $city->getMorphClass(),
            'subject_id' => $city->id,
            'url' => $document->url,
            'raw_payload' => $document->rawPayload,
            'normalized_payload' => $document->normalizedPayload,
            'fetched_at' => $document->fetchedAt,
            'hash' => hash('sha256', json_encode($document->rawPayload, JSON_THROW_ON_ERROR)),
            'http_status' => $document->httpStatus,
        ]);

        $facts = $connector->extractFacts($document, $city);

        foreach ($facts as $factData) {
            Fact::updateOrCreate(
                [
                    'subject_type' => $city->getMorphClass(),
                    'subject_id' => $city->id,
                    'key' => $factData->key,
                    'source_id' => $source->id,
                ],
                [
                    'value' => $factData->value,
                    'value_json' => $factData->valueJson,
                    'source_url' => $document->url,
                    'date_retrieved' => $document->fetchedAt,
                    // Pas de fusion inter-sources dans cette passe : la
                    // valeur reflète la fiabilité déclarée de CETTE source
                    // (`sources.reliability_score`) — comparer les valeurs
                    // entre sources est un algorithme distinct, non
                    // couvert ici (hypothèse déjà signalée "à valider"
                    // dans la migration d'origine des tables de faits).
                    'confidence_score' => $source->reliability_score,
                    'corroboration_count' => 1,
                    'last_verified_at' => $document->fetchedAt,
                ],
            );
        }

        return count($facts).' fait(s) collecté(s)';
    }
}
