<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildSectorPathAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Domain\Taxonomy\Actions\ShowSectorAction;
use App\Http\Api\V1\Resources\SectorResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SectorShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowSectorAction $action,
        BuildSectorPathAction $buildPath,
        TerritoryLinkingService $linkingService,
    ): SectorResource {
        $sector = $action->execute($resolvedCountry, (string) $request->route('slug'));

        // Attributs transitoires, jamais persistés : même patron que
        // `CityShowController` (Phase 13). `Sector` a une route par pays
        // (transversal) : la recherche filtre aussi par `country_id`.
        $sector->setAttribute('page_path', $buildPath->execute($sector, $resolvedCountry));
        $sector->setAttribute('page_links', $linkingService->forSector($sector, $resolvedCountry));
        $sector->setAttribute('page_route', PageRoute::query()
            ->where('country_id', $resolvedCountry->id)
            ->where('entity_type', $sector->getMorphClass())
            ->where('entity_id', $sector->id)
            ->where('page_type', PageType::Sector)
            ->first());

        return SectorResource::make($sector);
    }
}
