<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\District;

/**
 * Rattache une entreprise à son quartier par confinement géométrique réel
 * (`ST_Contains`) — jamais par proximité approchée (docs/DATABASE.md §4-A).
 * Recalcul idempotent : rejouable à tout moment, écrase toujours le
 * résultat courant, y compris vers `null` si plus aucun quartier ne
 * contient le point. Passe par une mise à jour en requête directe (pas
 * `$company->save()`) pour ne jamais redéclencher `CompanyObserver`.
 */
class ResolveCompanyDistrictAction
{
    public function execute(Company $company): void
    {
        // `latitude`/`longitude` sont des colonnes générées côté Postgres :
        // absentes de l'instance en mémoire tant qu'elle n'a pas été
        // relue depuis la base (l'INSERT d'Eloquent ne les rapatrie pas).
        $company = $company->fresh();

        if ($company === null || $company->latitude === null || $company->longitude === null || $company->city_id === null) {
            return;
        }

        $district = District::query()
            ->where('city_id', $company->city_id)
            ->whereNotNull('boundary')
            ->whereRaw('ST_Contains(boundary::geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326))', [
                $company->longitude,
                $company->latitude,
            ])
            ->first();

        Company::query()->whereKey($company->id)->update(['district_id' => $district?->id]);
    }
}
