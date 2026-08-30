<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Actions\ShowAdminDivisionAction;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Resources\AdminDivisionResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDivisionShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(Country $resolvedCountry, Request $request, ShowAdminDivisionAction $action): AdminDivisionResource
    {
        return AdminDivisionResource::make($action->execute($resolvedCountry, (string) $request->route('slug')));
    }
}
