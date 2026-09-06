<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\ShowCompanyAction;
use App\Domain\Content\Queries\CompanyPageBlocksQuery;
use App\Domain\Geo\Models\Country;
use App\Http\Api\V1\Resources\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(Country $resolvedCountry, Request $request, ShowCompanyAction $action, CompanyPageBlocksQuery $blocksQuery): CompanyResource
    {
        $company = $action->execute($resolvedCountry, (string) $request->route('slug'));

        // Attribut transitoire, jamais persisté : uniquement pour porter le
        // registre de blocs (CLAUDE.md §4) jusqu'à `CompanyResource` sans
        // dupliquer le contenu territorial en base (Phase 08).
        $company->setAttribute('page_blocks', $blocksQuery->execute($company));

        return CompanyResource::make($company);
    }
}
