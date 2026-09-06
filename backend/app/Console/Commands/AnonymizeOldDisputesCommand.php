<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Moderation\Actions\AnonymizeOldDisputeReportsAction;
use Illuminate\Console\Command;

class AnonymizeOldDisputesCommand extends Command
{
    protected $signature = 'disputes:anonymize';

    protected $description = 'Anonymise les signalements clos (Rejected/Applied) dont la rétention RGPD est dépassée';

    public function handle(AnonymizeOldDisputeReportsAction $action): int
    {
        $count = $action->execute();

        $this->info("{$count} signalement(s) anonymisé(s).");

        return self::SUCCESS;
    }
}
