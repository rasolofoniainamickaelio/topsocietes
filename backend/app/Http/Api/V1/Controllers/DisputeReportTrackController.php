<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Moderation\Models\DisputeReport;
use App\Http\Api\V1\Resources\DisputeReportTrackResource;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class DisputeReportTrackController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(Country $resolvedCountry, Request $request): DisputeReportTrackResource
    {
        $company = Company::query()
            ->where('country_id', $resolvedCountry->id)
            ->where('slug', (string) $request->route('slug'))
            ->first();

        if ($company === null) {
            throw new ModelNotFoundException;
        }

        $dispute = DisputeReport::query()
            ->where('company_id', $company->id)
            ->where('id', (string) $request->route('dispute'))
            ->with('events')
            ->first();

        if ($dispute === null) {
            throw new ModelNotFoundException;
        }

        return new DisputeReportTrackResource($dispute);
    }
}
