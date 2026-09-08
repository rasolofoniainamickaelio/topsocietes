<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Company\Actions\BuildCompanyPathAction;
use App\Domain\Company\Actions\ShowCompanyByIdAction;
use App\Domain\Content\Queries\CompanyPageBlocksQuery;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\InternalLinkingService;
use App\Http\Api\V1\Resources\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Miroir de `CompanyShowController`, lookup par `id` interne (Phase 16) —
 * seul consommateur prévu : le catch-all frontend, après résolution d'un
 * chemin via `/resolve` (qui renvoie un `entity_id` interne, jamais un
 * slug ni un `public_id`). Même orchestration que `CompanyShowController` ;
 * petite duplication assumée plutôt qu'une abstraction prématurée entre
 * deux controllers déjà volontairement indépendants.
 */
class CompanyShowByIdController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowCompanyByIdAction $action,
        CompanyPageBlocksQuery $blocksQuery,
        InternalLinkingService $linkingService,
        BuildCompanyPathAction $buildPath,
    ): CompanyResource {
        $company = $action->execute($resolvedCountry, (int) $request->route('id'));

        $company->setAttribute('page_blocks', $blocksQuery->execute($company));
        $company->setAttribute('page_links', $linkingService->forCompany($company));
        $company->setAttribute('page_path', $buildPath->execute($company));
        $company->setAttribute('page_route', PageRoute::query()
            ->where('entity_type', $company->getMorphClass())
            ->where('entity_id', $company->id)
            ->where('page_type', PageType::Company)
            ->first());

        return CompanyResource::make($company);
    }
}
