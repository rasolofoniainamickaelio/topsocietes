<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Import\Actions\RetryImportErrorsAction;
use App\Domain\Import\Models\ImportBatch;
use Illuminate\Console\Command;

/**
 * Relance uniquement les lignes en erreur non résolues d'un lot déjà
 * terminé — jamais tout le fichier (Phase 03, "relance ciblée des lignes en
 * échec").
 */
class RetryImportErrorsCommand extends Command
{
    protected $signature = 'import:retry-errors {batch : ID du lot d\'import}';

    protected $description = "Retraite les lignes en erreur non résolues d'un lot d'import";

    public function handle(RetryImportErrorsAction $action): int
    {
        $batch = ImportBatch::query()->find($this->argument('batch'));

        if ($batch === null) {
            $this->error("Lot d'import #{$this->argument('batch')} introuvable.");

            return self::FAILURE;
        }

        $result = $action->execute($batch);

        $this->info("Lignes résolues : {$result['resolved']}, toujours en échec : {$result['still_failing']}.");

        return self::SUCCESS;
    }
}
