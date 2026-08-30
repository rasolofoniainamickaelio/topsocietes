<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowCompanyAction
{
    public function execute(Country $country, string $slug): Company
    {
        $company = Company::query()
            ->where('country_id', $country->id)
            ->where('slug', $slug)
            ->with([
                'city',
                'district',
                'activity.sectors',
                'mainEstablishment',
                'nearbyPois.poi',
                // Jamais démasqué à la demande : `visibility` est déjà le
                // résultat du cycle d'abonnement (CLAUDE.md §6.3), cette
                // requête ne fait que le lire.
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
