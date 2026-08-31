<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\SearchCompaniesAction;
use App\Domain\Geo\Models\Country;
use App\Domain\Search\Data\SearchCompaniesData;
use App\Http\Api\V1\Requests\SearchCompaniesRequest;
use App\Http\Api\V1\Resources\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanySearchController extends Controller
{
    public function __invoke(Country $resolvedCountry, SearchCompaniesRequest $request, SearchCompaniesAction $action): AnonymousResourceCollection
    {
        $results = $action->execute($resolvedCountry, SearchCompaniesData::from($request->validated()));

        return CompanyResource::collection($results);
    }
}
