<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\ListCompaniesAction;
use App\Domain\Company\Data\ListCompaniesData;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Requests\ListCompaniesRequest;
use App\Http\Api\V1\Resources\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyIndexController extends Controller
{
    public function __invoke(Country $resolvedCountry, ListCompaniesRequest $request, ListCompaniesAction $action): AnonymousResourceCollection
    {
        $companies = $action->execute($resolvedCountry, ListCompaniesData::from($request->validated()));

        return CompanyResource::collection($companies);
    }
}
