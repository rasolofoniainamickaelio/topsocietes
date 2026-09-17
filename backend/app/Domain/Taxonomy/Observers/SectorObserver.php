<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Observers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Jobs\SyncSectorPageRouteJob;
use App\Domain\Taxonomy\Models\Sector;

/**
 * `Sector` est transversal (pas rattaché à un pays) : à la création ou au
 * changement de slug, une route est synchronisée pour CHAQUE pays actif
 * (boucle bornée à 6 pays aujourd'hui), pas un seul comme pour les autres
 * observateurs (City, AdminDivision, Activity).
 */
class SectorObserver
{
    public function created(Sector $sector): void
    {
        $this->syncAllCountries($sector);
    }

    public function updated(Sector $sector): void
    {
        if ($sector->isDirty('slug')) {
            $this->syncAllCountries($sector);
        }
    }

    private function syncAllCountries(Sector $sector): void
    {
        Country::query()->where('is_active', true)->get()->each(
            fn (Country $country) => SyncSectorPageRouteJob::dispatch($sector, $country),
        );
    }
}
