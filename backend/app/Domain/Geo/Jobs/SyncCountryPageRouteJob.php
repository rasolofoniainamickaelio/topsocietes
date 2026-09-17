<?php

declare(strict_types=1);

namespace App\Domain\Geo\Jobs;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildCountryPathAction;
use App\Domain\Seo\Actions\SyncPageRouteAction;
use App\Domain\Seo\Enums\PageType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Miroir de `SyncCityPageRouteJob` (Phase 13) pour `Country` : une seule
 * route par pays, toujours `/` — jamais synchrone sur une requête (Phase
 * 18).
 */
class SyncCountryPageRouteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Country $country)
    {
        $this->onQueue('geo');
    }

    public function handle(BuildCountryPathAction $buildPath, SyncPageRouteAction $sync): void
    {
        $sync->execute($this->country, $this->country, PageType::Country, $buildPath->execute());
    }
}
