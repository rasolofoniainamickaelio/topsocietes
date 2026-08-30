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
use App\Domain\Import\Support\RowTransformers;
use App\Domain\Taxonomy\Models\Activity;
use BackedEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Throwable;

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
        $errors = 0;

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 1;

            try {
                $outcome = $this->processRow($batch, $mapping, $country, $rawRow);

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
            'error_count' => $batch->error_count + $errors,
            'checkpoint' => ['offset' => $newOffset],
            'status' => $hasMore ? ImportStatus::Running : ImportStatus::Completed,
            'finished_at' => $hasMore ? null : now(),
        ]);

        return $hasMore;
    }

    /**
     * @param  array<string, mixed>  $rawRow
     * @return 'created'|'updated'|'skipped'
     */
    private function processRow(ImportBatch $batch, ImportMapping $mapping, Country $country, array $rawRow): string
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

        $city = $this->resolveCity($country->id, $mapped['postal_code'] ?? null);
        $activity = $this->resolveActivity($country, $mapped['activity_code'] ?? null);
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
        ];

        $dataHash = $this->hashFields($fields);

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
                'geocoding_status' => $city !== null ? GeocodingStatus::CityLevel : GeocodingStatus::Pending,
                'content_status' => CompanyContentStatus::Pending,
                'is_indexable' => true,
                'source_batch_id' => $batch->id,
                'location' => $this->locationExpression($city),
                'data_hash' => $dataHash,
            ]);

            return 'created';
        }

        if ($existing->data_hash === $dataHash) {
            return 'skipped';
        }

        $updateFields = [
            ...$fields,
            'source_batch_id' => $batch->id,
            'data_hash' => $dataHash,
        ];

        if ($city !== null) {
            $updateFields['geocoding_status'] = GeocodingStatus::CityLevel;
            $updateFields['location'] = $this->locationExpression($city);
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
            $value = $rawRow[$sourceColumn] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $value = $value === '' ? null : $value;

            $transformerName = $transformerNames[$targetField] ?? null;

            if ($transformerName !== null && $value !== null) {
                $value = $this->transformers->apply($transformerName, (string) $value);
            }

            $mapped[$targetField] = $value;
        }

        return $mapped;
    }

    private function resolveCity(int $countryId, ?string $postalCode): ?City
    {
        if (blank($postalCode)) {
            return null;
        }

        return City::query()
            ->where('country_id', $countryId)
            ->whereRaw('? = ANY(postal_codes)', [$postalCode])
            ->first();
    }

    private function resolveActivity(Country $country, ?string $code): ?Activity
    {
        if (blank($code) || blank($country->activity_nomenclature_code)) {
            return null;
        }

        return Activity::query()
            ->whereHas('nomenclature', fn ($query) => $query->where('code', $country->activity_nomenclature_code))
            ->where('code', $code)
            ->first();
    }

    private function locationExpression(?City $city): mixed
    {
        if ($city === null) {
            return null;
        }

        return DB::raw("ST_SetSRID(ST_MakePoint({$city->longitude}, {$city->latitude}), 4326)::geography");
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
     * @return array<int, array<string, mixed>>
     */
    private function readJsonRows(string $absolutePath, int $offset, int $limit): array
    {
        /** @var array<int, array<string, mixed>> $decoded */
        $decoded = json_decode(file_get_contents($absolutePath) ?: '[]', true) ?? [];

        return array_slice($decoded, $offset, $limit, true);
    }

    /**
     * Structure supposée en l'absence de fichier XML réel fourni : un
     * élément racine dont chaque enfant direct est une ligne, elle-même
     * composée d'attributs et/ou d'éléments enfants texte (les deux sont
     * lus comme colonnes). À ajuster dès qu'un fichier réel est disponible.
     * Ne passe aucun flag `LIBXML_NOENT`/`LIBXML_DTDLOAD` : la substitution
     * d'entités externes reste désactivée (protection XXE par défaut).
     *
     * @return array<int, array<string, mixed>>
     */
    private function readXmlRows(string $absolutePath, int $offset, int $limit): array
    {
        $xml = @simplexml_load_file($absolutePath);

        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $rows = [];
        $index = 0;

        foreach ($xml->children() as $rowElement) {
            if ($index >= $offset && $index < $offset + $limit) {
                $row = [];

                foreach ($rowElement->attributes() ?? [] as $name => $value) {
                    $row[(string) $name] = (string) $value;
                }

                foreach ($rowElement->children() as $child) {
                    $row[$child->getName()] = trim((string) $child);
                }

                $rows[$index] = $row;
            }

            $index++;

            if ($index >= $offset + $limit) {
                break;
            }
        }

        return $rows;
    }
}
