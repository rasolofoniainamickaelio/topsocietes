<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Jobs\ProcessImportBatchChunkJob;
use App\Domain\Import\Models\ImportBatch;
use Illuminate\Console\Command;

/**
 * Reprise manuelle après un crash de worker : `ProcessImportBatchChunkJob`
 * se redispatch normalement tout seul tant qu'il reste des lignes, cette
 * commande ne sert qu'à relancer la chaîne si elle s'est arrêtée sans
 * qu'un nouveau job n'ait été mis en file (Phase 03, "reprise après
 * interruption").
 */
class ResumeImportBatchCommand extends Command
{
    protected $signature = 'import:resume {batch : ID du lot d\'import à reprendre}';

    protected $description = "Reprend un lot d'import inachevé à partir de son checkpoint";

    public function handle(): int
    {
        $batch = ImportBatch::query()->find($this->argument('batch'));

        if ($batch === null) {
            $this->error("Lot d'import #{$this->argument('batch')} introuvable.");

            return self::FAILURE;
        }

        if ($batch->status === ImportStatus::Completed) {
            $this->info("Le lot #{$batch->id} est déjà terminé.");

            return self::SUCCESS;
        }

        ProcessImportBatchChunkJob::dispatch($batch);

        $offset = (int) ($batch->checkpoint['offset'] ?? 0);
        $this->info("Lot #{$batch->id} repris à partir de la ligne {$offset}.");

        return self::SUCCESS;
    }
}
