<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Actions\ListCitiesAction;
use App\Domain\Geo\Data\ListCitiesData;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Requests\ListCitiesRequest;
use App\Http\Api\V1\Resources\CityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CityIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry, ListCitiesRequest $request, ListCitiesAction $action): AnonymousResourceCollection
    {
        $cities = $action->execute($resolvedCountry, ListCitiesData::from($request->validated()));

        return CityResource::collection($cities);
    }
}
