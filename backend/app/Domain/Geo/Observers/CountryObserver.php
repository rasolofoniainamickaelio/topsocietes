<?php

declare(strict_types=1);

namespace App\Domain\Geo\Observers;

use App\Domain\Geo\Jobs\SyncCountryPageRouteJob;
use App\Domain\Geo\Models\Country;

/**
 * Miroir de `CityObserver` (Phase 13) pour `Country` — le chemin étant
 * toujours `/`, seule l'activation (`is_active`) justifie une
 * resynchronisation après la création (elle rejoue l'évaluation de
 * publication, jamais le chemin lui-même qui ne change pas).
 */
class CountryObserver
{
    public function created(Country $country): void
    {
        SyncCountryPageRouteJob::dispatch($country);
    }

    public function updated(Country $country): void
    {
        if ($country->isDirty('is_active')) {
            SyncCountryPageRouteJob::dispatch($country);
        }
    }
}
