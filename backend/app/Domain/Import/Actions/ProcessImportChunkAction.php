<?php

declare(strict_types=1);

namespace App\Domain\Import\Actions;

use App\Domain\Company\Actions\GenerateCompanySlugAction;
use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Enums\CompanyStatus;
use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Import\Enums\ImportFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Exceptions\MissingRequiredFieldException;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportError;
use App\Domain\Import\Models\ImportMapping;
use App\Domain\Import\Support\MoroccoCategoryNormalizer;
use App\Domain\Import\Support\RowTransformers;
use App\Domain\Taxonomy\Models\Activity;
use BackedEnum;
use DOMElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use XMLReader;

/**
 * Traite un lot de lignes à partir de `checkpoint.offset` et avance ce
 * dernier d'autant — c'est ce qui rend l'import reprenable après
 * interruption (Phase 03, "reprise après interruption"). Une ligne en
 * échec (champ requis manquant ou erreur technique) est journalisée dans
 * `import_errors` et n'interrompt jamais le reste du lot.
 */
class ProcessImportChunkAction
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly GenerateCompanySlugAction $slugAction,
        private readonly RowTransformers $transformers,
    ) {}

    /**
     * @return bool true si des lignes restent à traiter après ce lot
     */
    public function execute(ImportBatch $batch): bool
    {
        $mapping = $batch->mapping;

        if ($mapping === null) {
            throw new \RuntimeException("Le lot d'import #{$batch->id} n'a pas de mapping associé.");
        }

        $country = $batch->country;
        $offset = (int) ($batch->checkpoint['offset'] ?? 0);
        $chunkSize = (int) ($batch->options['chunk_size'] ?? self::CHUNK_SIZE);

        $absolutePath = Storage::disk('local')->path($batch->filename);
        $rows = $this->readRows($absolutePath, $batch->format, $offset, $chunkSize);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 1;

            try {
                $outcome = $this->processRow($batch, $mapping, $country, $rawRow, $duplicates);

                match ($outcome) {
                    'created' => $created++,
                    'updated' => $updated++,
                    'skipped' => $skipped++,
                };
            } catch (MissingRequiredFieldException $e) {
                $errors++;
                $this->logError($batch, $rowNumber, $rawRow, 'missing_required_field', $e->getMessage());
            } catch (Throwable $e) {
                $errors++;
                $this->logError($batch, $rowNumber, $rawRow, 'processing_error', $e->getMessage());
            }
        }

        $processedThisChunk = count($rows);
        $newOffset = $offset + $processedThisChunk;
        $hasMore = $processedThisChunk > 0 && $newOffset < $batch->total_rows;

        $batch->update([
            'processed_rows' => $batch->processed_rows + $processedThisChunk,
            'created_count' => $batch->created_count + $created,
            'updated_count' => $batch->updated_count + $updated,
            'skipped_count' => $batch->skipped_count + $skipped,
            'duplicate_count' => $batch->duplicate_count + $duplicates,
            'error_count' => $batch->error_count + $errors,
            'checkpoint' => ['offset' => $newOffset],
            'status' => $hasMore ? ImportStatus::Running : ImportStatus::Completed,
            'finished_at' => $hasMore ? null : now(),
        ]);

        return $hasMore;
    }

    /**
     * Retraite une seule ligne préalablement journalisée dans
     * `import_errors`, sans toucher au checkpoint ni aux compteurs agrégés
     * du lot — c'est `RetryImportErrorsAction` qui orchestre ces
     * ajustements (Phase 03, "relance ciblée des lignes en échec").
     *
     * @param  array<string, mixed>  $rawRow
     * @return 'created'|'updated'|'skipped'
     */
    public function retryRow(ImportBatch $batch, array $rawRow): string
    {
        $mapping = $batch->mapping;

        if ($mapping === null) {
            throw new \RuntimeException("Le lot d'import #{$batch->id} n'a pas de mapping associé.");
        }

        $duplicates = 0;

        return $this->processRow($batch, $mapping, $batch->country, $rawRow, $duplicates);
    }

    /**
     * @param  array<string, mixed>  $rawRow
     * @return 'created'|'updated'|'skipped'
     */
    private function processRow(ImportBatch $batch, ImportMapping $mapping, Country $country, array $rawRow, int &$duplicates): string
    {
        $mapped = $this->mapRow($mapping, $rawRow);

        $nationalId = $mapped['national_id'] ?? null;
        $legalName = $mapped['legal_name'] ?? null;

        if (blank($nationalId) || blank($legalName)) {
            $missing = array_filter([
                blank($nationalId) ? 'national_id' : null,
                blank($legalName) ? 'legal_name' : null,
            ]);

            throw new MissingRequiredFieldException('Champ(s) requis manquant(s) : '.implode(', ', $missing));
        }

        $city = $this->resolveCity($country, $mapped);
        $activity = $this->resolveActivity($country, $mapped);
        $status = CompanyStatus::tryFrom(mb_strtolower((string) ($mapped['status'] ?? ''))) ?? CompanyStatus::Unknown;

        $fields = [
            'legal_name' => $legalName,
            'trade_name' => $mapped['trade_name'] ?? null,
            'legal_form_code' => $mapped['legal_form_code'] ?? null,
            'legal_form_label' => $mapped['legal_form_label'] ?? null,
            'status' => $status,
            'created_date' => $mapped['created_date'] ?? null,
            'ceased_date' => $mapped['ceased_date'] ?? null,
            'activity_id' => $activity?->id,
            'activity_code_raw' => $mapped['activity_code'] ?? null,
            'headcount_range' => $mapped['headcount_range'] ?? null,
            'city_id' => $city?->id,
            // Rattachement département/province transitif : une commune
            // porte déjà sa division administrative (Phase 04, "rattachement
            // automatique au pays/région/département").
            'admin_division_id' => $city?->admin_division_id,
        ];

        $dataHash = $this->hashFields($fields);
        $geocoding = $this->resolveGeocoding($country, $mapped, $city);

        $existing = Company::query()
            ->where('country_id', $country->id)
            ->where('national_id', $nationalId)
            ->first();

        if ($existing === null) {
            Company::query()->create([
                ...$fields,
                'country_id' => $country->id,
                'national_id' => $nationalId,
                'slug' => $this->slugAction->execute($country, $legalName, $city),
                'geocoding_status' => $geocoding['status'],
                'content_status' => CompanyContentStatus::Pending,
                'is_indexable' => true,
                'source_batch_id' => $batch->id,
                'location' => $geocoding['location'],
                'data_hash' => $dataHash,
            ]);

            return 'created';
        }

        // Deux lignes du MÊME fichier qui résolvent à la même entreprise
        // (même `national_id`) : un doublon intra-lot, distinct d'une mise à
        // jour légitime d'une fiche déjà connue avant ce lot (Phase 03,
        // "détection des doublons").
        if ($existing->source_batch_id === $batch->id) {
            $duplicates++;
        }

        if ($existing->data_hash === $dataHash) {
            return 'skipped';
        }

        // `city_id`/`admin_division_id` restent dans `$fields` pour le hash
        // (un changement de ville doit se voir), mais ne sont écrits que
        // lorsque cette ligne résout effectivement une ville : une absence
        // ponctuelle de code postal exploitable ne doit jamais régresser un
        // rattachement géo déjà acquis sur la fiche existante.
        $updateFields = [
            ...array_diff_key($fields, ['city_id' => null, 'admin_division_id' => null]),
            'source_batch_id' => $batch->id,
            'data_hash' => $dataHash,
        ];

        if ($city !== null) {
            $updateFields['city_id'] = $city->id;
            $updateFields['admin_division_id'] = $city->admin_division_id;
        }

        // Ne jamais régresser un géocodage déjà acquis (ex. des coordonnées
        // exactes obtenues sur un import précédent) au motif qu'une ligne
        // ultérieure est moins précise ou ne résout plus rien.
        if ($this->isBetterGeocoding($geocoding['status'], $existing->geocoding_status)) {
            $updateFields['geocoding_status'] = $geocoding['status'];
            $updateFields['location'] = $geocoding['location'];
        }

        $existing->update($updateFields);

        return 'updated';
    }

    /**
     * @param  array<string, mixed>  $rawRow
     * @return array<string, mixed>
     */
    private function mapRow(ImportMapping $mapping, array $rawRow): array
    {
        /** @var array<string, string> $columnMap */
        $columnMap = $mapping->column_map;
        /** @var array<string, string> $transformerNames */
        $transformerNames = $mapping->transformers ?? [];

        $mapped = [];

        foreach ($columnMap as $sourceColumn => $targetField) {
            $transformerName = $transformerNames[$targetField] ?? null;

            // Toute colonne source préfixée `__row` (ex. `__row_national_id__`,
            // `__row_city_slug__` — un préfixe distinct par champ cible, un
            // simple `array_map` ne pouvant pas porter deux clés `__row__`
            // identiques) : le champ cible est dérivé de plusieurs colonnes à
            // la fois, pas d'une colonne source unique — voir
            // `RowTransformers::applyRow()`.
            if (str_starts_with($sourceColumn, '__row')) {
                $mapped[$targetField] = $transformerName !== null
                    ? $this->transformers->applyRow($transformerName, $rawRow)
                    : null;

                continue;
            }

            $value = $rawRow[$sourceColumn] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $value = $value === '' ? null : $value;

            if ($transformerName !== null && $value !== null) {
                $value = $this->transformers->apply($transformerName, (string) $value);
            }

            $mapped[$targetField] = $value;
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $mapped
     */
    private function resolveCity(Country $country, array $mapped): ?City
    {
        // Piloté par `countries.settings` (CLAUDE.md §6.6) : jamais de
        // branchement sur le code pays ici. Par défaut, résolution par code
        // postal exact (`? = ANY(postal_codes)`) ; le Maroc n'a pas de
        // colonne code postal exploitable dans son fichier source (voir
        // `MoroccoAddressResolver`) et résout par slug de ville à la place.
        $strategy = $country->settings['city_resolution'] ?? 'postal_code';

        if ($strategy === 'slug') {
            $slug = $mapped['city_slug'] ?? null;

            if (blank($slug)) {
                return null;
            }

            return City::query()->where('country_id', $country->id)->where('slug', $slug)->first();
        }

        $postalCode = $mapped['postal_code'] ?? null;

        if (blank($postalCode)) {
            return null;
        }

        return City::query()
            ->where('country_id', $country->id)
            ->whereRaw('? = ANY(postal_codes)', [$postalCode])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $mapped
     */
    private function resolveActivity(Country $country, array $mapped): ?Activity
    {
        if (blank($country->activity_nomenclature_code)) {
            return null;
        }

        // Piloté par `countries.settings` (CLAUDE.md §6.6), même principe que
        // `resolveCity()`. Le Maroc n'a pas de nomenclature d'activité
        // officielle (voir `MoroccoActivitySeeder`) : le code recherché est
        // recalculé depuis le texte libre de la ligne, avec le même
        // algorithme que celui qui a servi à seeder la table (cf.
        // `MoroccoCategoryNormalizer`).
        $strategy = $country->settings['activity_resolution'] ?? 'nomenclature_code';

        $code = $strategy === 'category_key'
            ? MoroccoCategoryNormalizer::key((string) ($mapped['activity_category_raw'] ?? ''))
            : ($mapped['activity_code'] ?? null);

        if (blank($code)) {
            return null;
        }

        return Activity::query()
            ->whereHas('nomenclature', fn ($query) => $query->where('code', $country->activity_nomenclature_code))
            ->where('code', $code)
            ->first();
    }

    /**
     * Détermine le statut de géocodage et la position à écrire, par ordre de
     * précision décroissant (Phase 04) : des coordonnées exactes fournies
     * par le fichier source priment toujours sur le centroïde de la ville
     * résolue ; en l'absence des deux, distingue un rattachement qui n'a
     * jamais été tenté (`Pending`, aucune colonne d'adresse exploitable dans
     * la ligne) d'un rattachement tenté sans succès (`Failed`).
     *
     * @param  array<string, mixed>  $mapped
     * @return array{status: GeocodingStatus, location: mixed}
     */
    private function resolveGeocoding(Country $country, array $mapped, ?City $city): array
    {
        $latitude = $this->toFloatOrNull($mapped['latitude'] ?? null);
        $longitude = $this->toFloatOrNull($mapped['longitude'] ?? null);

        if ($latitude !== null && $longitude !== null) {
            return ['status' => GeocodingStatus::Exact, 'location' => $this->locationExpression($longitude, $latitude)];
        }

        if ($city !== null) {
            return [
                'status' => GeocodingStatus::CityLevel,
                'location' => $this->locationExpression((float) $city->longitude, (float) $city->latitude),
            ];
        }

        if ($this->addressAttempted($country, $mapped)) {
            return ['status' => GeocodingStatus::Failed, 'location' => null];
        }

        return ['status' => GeocodingStatus::Pending, 'location' => null];
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $mapped
     */
    private function addressAttempted(Country $country, array $mapped): bool
    {
        $strategy = $country->settings['city_resolution'] ?? 'postal_code';

        return $strategy === 'slug'
            ? filled($mapped['city_slug'] ?? null)
            : filled($mapped['postal_code'] ?? null);
    }

    /**
     * Ordre de précision croissant : Pending < Failed < CityLevel <
     * Approximate < Exact. `Failed` n'est "meilleur" que face à `Pending`
     * (un échec constaté vaut mieux qu'une absence de tentative pour le
     * reporting de reprise manuelle), jamais face à un géocodage déjà acquis.
     */
    private function isBetterGeocoding(GeocodingStatus $new, GeocodingStatus $current): bool
    {
        $rank = [
            GeocodingStatus::Pending->value => 0,
            GeocodingStatus::Failed->value => 1,
            GeocodingStatus::CityLevel->value => 2,
            GeocodingStatus::Approximate->value => 3,
            GeocodingStatus::Exact->value => 4,
        ];

        return $rank[$new->value] > $rank[$current->value];
    }

    private function locationExpression(float $longitude, float $latitude): mixed
    {
        return DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)::geography");
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function hashFields(array $fields): string
    {
        $normalized = array_map(
            static fn ($value) => $value instanceof BackedEnum ? $value->value : $value,
            $fields,
        );

        ksort($normalized);

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $rawRow
     */
    private function logError(ImportBatch $batch, int $rowNumber, array $rawRow, string $code, string $message): void
    {
        ImportError::query()->create([
            'batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'raw_row' => $rawRow,
            'error_code' => $code,
            'error_message' => $message,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRows(string $absolutePath, ImportFormat $format, int $offset, int $limit): array
    {
        return match ($format) {
            ImportFormat::Json => $this->readJsonRows($absolutePath, $offset, $limit),
            ImportFormat::Xml => $this->readXmlRows($absolutePath, $offset, $limit),
            ImportFormat::Csv => $this->readCsvRows($absolutePath, $offset, $limit),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsvRows(string $absolutePath, int $offset, int $limit): array
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                return [];
            }

            $index = 0;

            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null]) {
                    continue;
                }

                if ($index >= $offset && $index < $offset + $limit) {
                    if (count($line) !== count($header)) {
                        $line = array_pad(array_slice($line, 0, count($header)), count($header), null);
                    }

                    $rows[$index] = array_combine($header, $line);
                }

                $index++;

                if ($index >= $offset + $limit) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * JSON Lines : un objet par ligne, lu au fil de l'eau avec `fgets` — le
     * fichier entier n'est jamais chargé ni reparsé en mémoire, quelle que
     * soit sa taille (Phase 03, "jamais tout en mémoire").
     *
     * @return array<int, array<string, mixed>>
     */
    private function readJsonRows(string $absolutePath, int $offset, int $limit): array
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        try {
            $index = 0;

            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if ($index >= $offset && $index < $offset + $limit) {
                    /** @var array<string, mixed>|null $decoded */
                    $decoded = json_decode($line, true);
                    $rows[$index] = is_array($decoded) ? $decoded : [];
                }

                $index++;

                if ($index >= $offset + $limit) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Structure supposée en l'absence de fichier XML réel fourni : un
     * élément racine dont chaque enfant direct est une ligne, elle-même
     * composée d'attributs et/ou d'éléments enfants texte (les deux sont
     * lus comme colonnes). À ajuster dès qu'un fichier réel est disponible.
     *
     * Lu avec `XMLReader` (curseur) plutôt que `simplexml_load_file` : seul
     * l'élément en cours d'inspection est développé en DOM
     * (`XMLReader::expand()`), jamais le document entier. Le chargement de
     * DTD externe et la substitution d'entités restent désactivés (protection
     * XXE).
     *
     * @return array<int, array<string, mixed>>
     */
    private function readXmlRows(string $absolutePath, int $offset, int $limit): array
    {
        $reader = new XMLReader;

        if (! $reader->open($absolutePath, flags: LIBXML_NONET)) {
            return [];
        }

        $reader->setParserProperty(XMLReader::LOADDTD, false);
        $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);

        $rows = [];
        $index = 0;

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->depth !== 1) {
                    continue;
                }

                if ($index >= $offset && $index < $offset + $limit) {
                    $rows[$index] = $this->xmlNodeToRow($reader);
                } else {
                    $reader->next();
                }

                $index++;

                if ($index >= $offset + $limit) {
                    break;
                }
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function xmlNodeToRow(XMLReader $reader): array
    {
        $node = $reader->expand();
        $row = [];

        if (! $node instanceof DOMElement) {
            return $row;
        }

        foreach ($node->attributes as $attribute) {
            $row[$attribute->nodeName] = $attribute->nodeValue;
        }

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $row[$child->nodeName] = trim($child->textContent);
            }
        }

        return $row;
    }
}
