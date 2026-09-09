<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\BuildCompanyPathAction;
use App\Domain\Company\Actions\ListCompaniesAction;
use App\Domain\Company\Data\ListCompaniesData;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Requests\ListCompaniesRequest;
use App\Http\Api\V1\Resources\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyIndexController extends Controller
{
    public function __invoke(
        Country $resolvedCountry,
        ListCompaniesRequest $request,
        ListCompaniesAction $action,
        BuildCompanyPathAction $buildPath,
    ): AnonymousResourceCollection {
        $companies = $action->execute($resolvedCountry, ListCompaniesData::from($request->validated()));

        // Même attribut transitoire `page_path` que `CompanyShowController`
        // (Phase 16) : sans lui, une liste d'entreprises (Phase 12) n'aurait
        // aucun lien exploitable vers chaque fiche. `items()` (contrat
        // `CursorPaginator`) plutôt que `getCollection()` (absente du
        // contrat, seulement sur la classe concrète) — chaque `Company`
        // reste le même objet à l'intérieur du paginator, `setAttribute`
        // suffit sans avoir à réinjecter la collection.
        foreach ($companies->items() as $company) {
            $company->setAttribute('page_path', $buildPath->execute($company));
        }

        return CompanyResource::collection($companies);
    }
}
