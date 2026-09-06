<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\ShowCompanyAction;
use App\Domain\Content\Queries\CompanyPageBlocksQuery;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Services\InternalLinkingService;
use App\Http\Api\V1\Resources\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowCompanyAction $action,
        CompanyPageBlocksQuery $blocksQuery,
        InternalLinkingService $linkingService,
    ): CompanyResource {
        $company = $action->execute($resolvedCountry, (string) $request->route('slug'));

        // Attributs transitoires, jamais persistés : uniquement pour porter
        // le registre de blocs (CLAUDE.md §4, Phase 08) et le maillage
        // interne (Phase 15) jusqu'à `CompanyResource`.
        $company->setAttribute('page_blocks', $blocksQuery->execute($company));
        $company->setAttribute('page_links', $linkingService->forCompany($company));

        return CompanyResource::make($company);
    }
}
