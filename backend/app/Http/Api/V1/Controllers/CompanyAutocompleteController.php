<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\SearchCompaniesAction;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Resources\CompanySuggestionResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyAutocompleteController extends Controller
{
    public function __invoke(Country $resolvedCountry, Request $request, SearchCompaniesAction $action): AnonymousResourceCollection
    {
        $term = (string) $request->query('term', '');

        return CompanySuggestionResource::collection($action->autocomplete($resolvedCountry, $term));
    }
}
