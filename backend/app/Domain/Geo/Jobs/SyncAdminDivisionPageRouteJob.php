<?php

declare(strict_types=1);

namespace App\Domain\Geo\Jobs;

use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Seo\Actions\BuildAdminDivisionPathAction;
use App\Domain\Seo\Actions\SyncPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Miroir de `SyncCityPageRouteJob` (Phase 13) pour `AdminDivision` (les deux
 * niveaux, région et département/province/...) — jamais synchrone sur une
 * requête (Phase 18).
 */
class SyncAdminDivisionPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly AdminDivision $division)
    {
        $this->onQueue('geo');
    }

    public function handle(BuildAdminDivisionPathAction $buildPath, SyncPageRouteAction $sync): void
    {
        $sync->execute($this->division, $this->division->country, PageType::AdminDivision, $buildPath->execute($this->division, $this->division->country));
    }
}
