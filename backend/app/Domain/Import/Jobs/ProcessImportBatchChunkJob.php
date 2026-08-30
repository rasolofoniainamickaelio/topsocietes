<?php

declare(strict_types=1);

namespace App\Domain\Import\Jobs;

use App\Domain\Import\Actions\ProcessImportChunkAction;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Se redispatch lui-même tant qu'il reste des lignes à traiter, ce qui
 * garde chaque exécution courte et bornée en mémoire plutôt qu'un job
 * unique parcourant tout le fichier (Phase 03, "traitement par lots").
 */
class ProcessImportBatchChunkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ImportBatch $batch) {}

    public function handle(ProcessImportChunkAction $action): void
    {
        if ($this->batch->status === ImportStatus::Pending) {
            $this->batch->update(['status' => ImportStatus::Running, 'started_at' => now()]);
        }

        $hasMore = $action->execute($this->batch);

        if ($hasMore) {
            self::dispatch($this->batch->fresh());
        }
    }
}
