<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Actions\ShowSectorAction;
use App\Http\Api\V1\Resources\SectorResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SectorShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(Country $resolvedCountry, Request $request, ShowSectorAction $action): SectorResource
    {
        return SectorResource::make($action->execute($resolvedCountry, (string) $request->route('slug')));
    }
}
