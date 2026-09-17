<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildActivityPathAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Domain\Taxonomy\Actions\ShowActivityAction;
use App\Http\Api\V1\Resources\ActivityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActivityShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement du
    // paramètre de route par `Request::route()`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowActivityAction $action,
        BuildActivityPathAction $buildPath,
        TerritoryLinkingService $linkingService,
    ): ActivityResource {
        $activity = $action->execute($resolvedCountry, (string) $request->route('slug'));

        // Attributs transitoires, jamais persistés : même patron que
        // `CityShowController` (Phase 13).
        $activity->setAttribute('page_path', $buildPath->execute($activity, $resolvedCountry));
        $activity->setAttribute('page_links', $linkingService->forActivity($activity, $resolvedCountry));
        $activity->setAttribute('page_route', PageRoute::query()
            ->where('entity_type', $activity->getMorphClass())
            ->where('entity_id', $activity->id)
            ->where('page_type', PageType::Activity)
            ->first());

        return ActivityResource::make($activity);
    }
}
