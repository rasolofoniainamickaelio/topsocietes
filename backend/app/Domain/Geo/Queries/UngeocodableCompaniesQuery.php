<?php

declare(strict_types=1);

namespace App\Domain\Geo\Queries;

use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Builder;

/**
 * File de reprise manuelle des entreprises jamais géocodées (`Pending`) ou
 * dont le rattachement a été tenté sans succès (`Failed`) — Phase 04,
 * "gérer les adresses non géocodables (file de reprise manuelle)".
 */
class UngeocodableCompaniesQuery
{
    /**
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    public function apply(Builder $query): Builder
    {
        return $query->whereIn('geocoding_status', [
            GeocodingStatus::Pending->value,
            GeocodingStatus::Failed->value,
        ]);
    }
}
