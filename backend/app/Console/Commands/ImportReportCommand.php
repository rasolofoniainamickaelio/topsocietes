<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Import\Models\ImportBatch;
use Illuminate\Console\Command;

/**
 * Rapport chiffré d'un lot d'import (livrable Phase 19) — base pour la
 * Phase 20. Lit les compteurs du batch + options enrichies (dry-run /
 * résolution ville-activité).
 */
class ImportReportCommand extends Command
{
    protected $signature = 'import:report
        {batch : ID du lot d\'import}
        {--json : Sortie JSON brute}';

    protected $description = 'Affiche le rapport chiffré d\'un lot d\'import (Phase 19)';

    public function handle(): int
    {
        $batch = ImportBatch::query()->find($this->argument('batch'));

        if ($batch === null) {
            $this->error("Lot #{$this->argument('batch')} introuvable.");

            return self::FAILURE;
        }

        $options = $batch->options ?? [];
        $processed = max(1, $batch->processed_rows);
        $durationSeconds = null;

        if ($batch->started_at !== null && $batch->finished_at !== null) {
            $durationSeconds = $batch->started_at->diffInSeconds($batch->finished_at);
        }

        $report = [
            'batch_id' => $batch->id,
            'status' => $batch->status->value,
            'dry_run' => (bool) ($options['dry_run'] ?? false),
            'filename' => $batch->filename,
            'total_rows' => $batch->total_rows,
            'processed_rows' => $batch->processed_rows,
            'created_count' => $batch->created_count,
            'updated_count' => $batch->updated_count,
            'skipped_count' => $batch->skipped_count,
            'duplicate_count' => $batch->duplicate_count,
            'error_count' => $batch->error_count,
            'error_rate_pct' => round(($batch->error_count / $processed) * 100, 2),
            'city_resolved_count' => (int) ($options['city_resolved_count'] ?? 0),
            'activity_resolved_count' => (int) ($options['activity_resolved_count'] ?? 0),
            'city_resolved_pct' => round((((int) ($options['city_resolved_count'] ?? 0)) / $processed) * 100, 2),
            'activity_resolved_pct' => round((((int) ($options['activity_resolved_count'] ?? 0)) / $processed) * 100, 2),
            'error_codes' => $options['error_codes'] ?? [],
            'duration_seconds' => $durationSeconds,
            'rows_per_minute' => $durationSeconds !== null && $durationSeconds > 0
                ? round(($batch->processed_rows / $durationSeconds) * 60, 1)
                : null,
            'started_at' => $batch->started_at?->toIso8601String(),
            'finished_at' => $batch->finished_at?->toIso8601String(),
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info("Rapport lot #{$batch->id}".(($report['dry_run']) ? ' [dry-run]' : ''));
        $this->table(
            ['Indicateur', 'Valeur'],
            collect($report)
                ->except(['error_codes'])
                ->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (is_array($value) ? json_encode($value) : (string) ($value ?? '—'))])
                ->values()
                ->all(),
        );

        if ($report['error_codes'] !== []) {
            $this->newLine();
            $this->warn('Codes d\'erreur :');
            foreach ($report['error_codes'] as $code => $count) {
                $this->line("  - {$code}: {$count}");
            }
        }

        return self::SUCCESS;
    }
}
