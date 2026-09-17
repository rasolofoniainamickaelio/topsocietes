<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Actions\ResolveAdminDivisionLevelLabelAction;
use App\Domain\Geo\Actions\ShowAdminDivisionAction;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildAdminDivisionPathAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Http\Api\V1\Resources\AdminDivisionResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDivisionShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowAdminDivisionAction $action,
        BuildAdminDivisionPathAction $buildPath,
        TerritoryLinkingService $linkingService,
        ResolveAdminDivisionLevelLabelAction $resolveLevelLabel,
    ): AdminDivisionResource {
        $division = $action->execute($resolvedCountry, (string) $request->route('slug'));

        // Attributs transitoires, jamais persistés : même patron que
        // `CityShowController` (Phase 13).
        $division->setAttribute('page_path', $buildPath->execute($division, $resolvedCountry));
        $division->setAttribute('page_links', $linkingService->forAdminDivision($division, $resolvedCountry));
        $division->setAttribute('page_level_label', $resolveLevelLabel->execute($resolvedCountry, $division->level));
        $division->setAttribute('page_route', PageRoute::query()
            ->where('entity_type', $division->getMorphClass())
            ->where('entity_id', $division->id)
            ->where('page_type', PageType::AdminDivision)
            ->first());

        return AdminDivisionResource::make($division);
    }
}
