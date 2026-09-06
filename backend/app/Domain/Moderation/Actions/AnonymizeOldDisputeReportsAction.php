<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Support\Facades\Storage;

/**
 * Anonymisation RGPD (Phase 22) : purge les données personnelles d'un
 * signalement clos (Rejected/Applied) dont `resolved_at` dépasse la durée
 * de rétention — jamais un signalement encore en cours de traitement, dont
 * les coordonnées du demandeur restent nécessaires. `anonymized_at` évite
 * de retraiter une ligne déjà anonymisée à chaque exécution planifiée.
 */
class AnonymizeOldDisputeReportsAction
{
    private const RETENTION_MONTHS = 24;

    public function execute(): int
    {
        $count = 0;

        DisputeReport::query()
            ->whereIn('status', [DisputeStatus::Rejected, DisputeStatus::Applied])
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', now()->subMonths(self::RETENTION_MONTHS))
            ->whereNull('anonymized_at')
            ->cursor()
            ->each(function (DisputeReport $dispute) use (&$count): void {
                if ($dispute->evidence_path !== null) {
                    Storage::disk('local')->delete($dispute->evidence_path);
                }

                $dispute->update([
                    'reporter_name' => null,
                    'reporter_email' => null,
                    'reporter_phone' => null,
                    'evidence_path' => null,
                    'ip_hash' => null,
                    'anonymized_at' => now(),
                ]);

                $count++;
            });

        return $count;
    }
}
