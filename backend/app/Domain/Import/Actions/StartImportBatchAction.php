<?php

declare(strict_types=1);

namespace App\Domain\Import\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Import\Enums\ImportFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Jobs\ProcessImportBatchChunkJob;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportMapping;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use SplFileObject;

/**
 * Crée le lot d'import et dispatch le premier morceau de traitement.
 * `$storedPath` est un chemin relatif au disque `local`
 * (`storage/app/private/...`), réutilisé tel quel comme `filename` sur le
 * batch : c'est aussi l'endroit où `ProcessImportChunkAction` relit le
 * fichier.
 */
class StartImportBatchAction
{
    public function execute(Country $country, string $storedPath, ImportFormat $format, ImportMapping $mapping, ?User $triggeredBy = null): ImportBatch
    {
        $absolutePath = Storage::disk('local')->path($storedPath);

        $batch = ImportBatch::query()->create([
            'country_id' => $country->id,
            'filename' => $storedPath,
            'format' => $format,
            'mapping_id' => $mapping->id,
            'triggered_by' => $triggeredBy?->id,
            'total_rows' => $this->countRows($absolutePath, $format),
            'status' => ImportStatus::Pending,
            'checkpoint' => ['offset' => 0],
        ]);

        ProcessImportBatchChunkJob::dispatch($batch);

        return $batch;
    }

    private function countRows(string $absolutePath, ImportFormat $format): int
    {
        if ($format === ImportFormat::Json) {
            /** @var array<int, mixed> $rows */
            $rows = json_decode(file_get_contents($absolutePath) ?: '[]', true) ?? [];

            return count($rows);
        }

        if ($format === ImportFormat::Xml) {
            $xml = @simplexml_load_file($absolutePath);

            return $xml instanceof SimpleXMLElement ? count($xml->children()) : 0;
        }

        $file = new SplFileObject($absolutePath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $count = -1; // la première ligne lue est l'en-tête, exclue du total
        foreach ($file as $line) {
            if ($this->isBlankLine($line)) {
                continue;
            }

            $count++;
        }

        return max($count, 0);
    }

    private function isBlankLine(mixed $line): bool
    {
        if (! is_array($line)) {
            return true;
        }

        foreach ($line as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
