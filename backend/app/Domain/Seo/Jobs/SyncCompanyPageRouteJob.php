<?php

declare(strict_types=1);

namespace App\Domain\Seo\Jobs;

use App\Domain\Company\Actions\BuildCompanyPathAction;
use App\Domain\Company\Models\Company;
use App\Domain\Seo\Actions\SyncPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Jamais synchrone sur une requête (Phase 18) : la synchronisation de route
 * et l'évaluation d'indexabilité qu'elle déclenche partent toujours en file.
 */
class SyncCompanyPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Company $company)
    {
        $this->onQueue('geo');
    }

    public function handle(BuildCompanyPathAction $buildPath, SyncPageRouteAction $sync): void
    {
        $sync->execute($this->company, $this->company->country, PageType::Company, $buildPath->execute($this->company));
    }
}
