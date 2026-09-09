<?php

declare(strict_types=1);

namespace App\Domain\Geo\Jobs;

use App\Domain\Geo\Models\City;
use App\Domain\Seo\Actions\BuildCityPathAction;
use App\Domain\Seo\Actions\SyncPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Miroir de `SyncCompanyPageRouteJob` (Phase 16) pour `City` (Phase 13) —
 * jamais synchrone sur une requête (Phase 18).
 */
class SyncCityPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly City $city)
    {
        $this->onQueue('geo');
    }

    public function handle(BuildCityPathAction $buildPath, SyncPageRouteAction $sync): void
    {
        $sync->execute($this->city, $this->city->country, PageType::City, $buildPath->execute($this->city, $this->city->country));
    }
}
