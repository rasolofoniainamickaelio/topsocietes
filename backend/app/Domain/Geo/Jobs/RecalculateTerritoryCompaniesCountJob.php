<?php

declare(strict_types=1);

namespace App\Domain\Geo\Jobs;

use App\Domain\Geo\Actions\RecalculateTerritoryCompaniesCountAction;
use App\Domain\Seo\Enums\PageType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Jamais calculé à l'affichage (CLAUDE.md §3, config/horizon.php —
 * supervisor-geo anticipe explicitement ce job depuis la Phase 01).
 */
class RecalculateTerritoryCompaniesCountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly PageType $pageType, public readonly int $entityId)
    {
        $this->onQueue('geo');
    }

    public function handle(RecalculateTerritoryCompaniesCountAction $action): void
    {
        $action->execute($this->pageType, $this->entityId);
    }
}
