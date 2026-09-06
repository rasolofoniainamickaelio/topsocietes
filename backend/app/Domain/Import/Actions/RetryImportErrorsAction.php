<?php

declare(strict_types=1);

namespace App\Domain\Import\Actions;

use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportError;
use Throwable;

/**
 * Ne retraite que les lignes d'un lot marquées en erreur et non résolues —
 * jamais tout le lot — en réutilisant exactement la même logique de mapping/
 * upsert que `ProcessImportChunkAction` (Phase 03, "relance ciblée des
 * lignes en échec").
 */
class RetryImportErrorsAction
{
    public function __construct(private readonly ProcessImportChunkAction $processor) {}

    /**
     * @return array{resolved: int, still_failing: int}
     */
    public function execute(ImportBatch $batch): array
    {
        $resolved = 0;
        $stillFailing = 0;

        ImportError::query()
            ->where('batch_id', $batch->id)
            ->where('is_resolved', false)
            ->orderBy('id')
            ->lazyById()
            ->each(function (ImportError $error) use ($batch, &$resolved, &$stillFailing): void {
                try {
                    $outcome = $this->processor->retryRow($batch, $error->raw_row ?? []);

                    $error->update(['is_resolved' => true, 'retried_at' => now()]);

                    $batch->increment(match ($outcome) {
                        'created' => 'created_count',
                        'updated' => 'updated_count',
                        'skipped' => 'skipped_count',
                    });
                    $batch->decrement('error_count');

                    $resolved++;
                } catch (Throwable $e) {
                    $error->update(['retried_at' => now(), 'error_message' => $e->getMessage()]);

                    $stillFailing++;
                }
            });

        return ['resolved' => $resolved, 'still_failing' => $stillFailing];
    }
}
