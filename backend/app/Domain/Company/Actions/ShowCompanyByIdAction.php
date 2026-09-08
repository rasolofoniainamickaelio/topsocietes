<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Miroir de `ShowCompanyAction`, lookup par `id` interne plutôt que par
 * `slug` — nécessaire car `slug` n'a aucune contrainte unique en base (seul
 * `(country_id, national_id)` l'est) : c'est `public_id` (ULID), jamais le
 * slug seul, qui identifie une fiche sans ambiguïté dans une URL (Phase 16).
 * Consommée uniquement par `/resolve` (Domain\Seo), qui renvoie l'`id`
 * interne d'une route résolue — jamais directement depuis une URL publique.
 */
class ShowCompanyByIdAction
{
    public function execute(Country $country, int $id): Company
    {
        $company = Company::query()
            ->where('country_id', $country->id)
            ->where('id', $id)
            ->with([
                'city',
                'district',
                'activity.sectors',
                'mainEstablishment',
                'nearbyPois.poi',
                'contacts' => fn ($query) => $query->whereIn('visibility', [
                    ContactVisibility::Visible,
                    ContactVisibility::ForcedVisible,
                ]),
            ])
            ->first();

        if ($company === null) {
            throw new ModelNotFoundException;
        }

        return $company;
    }
}
