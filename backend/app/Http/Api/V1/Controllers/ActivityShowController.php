<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Actions\ShowActivityAction;
use App\Http\Api\V1\Resources\ActivityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActivityShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(Country $resolvedCountry, Request $request, ShowActivityAction $action): ActivityResource
    {
        return ActivityResource::make($action->execute($resolvedCountry, (string) $request->route('slug')));
    }
}
