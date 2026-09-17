<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Content\Queries\ActivityCityPageQuery;
use App\Domain\Geo\Actions\ShowCityAction;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildActivityPathAction;
use App\Domain\Seo\Actions\BuildCityPathAction;
use App\Domain\Seo\Data\ActivityCityPageData;
use App\Domain\Taxonomy\Actions\ShowActivityAction;
use App\Http\Api\V1\Resources\ActivityCityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Page composite (Phase 12) : pas d'entité Eloquent unique, `ShowCityAction`
 * et `ShowActivityAction` fournissent chacune leur 404 propre. L'assemblage
 * lourd (blocs, voisins, indexabilité) est caché via `ActivityCityPageQuery`
 * (Phase 20).
 */
class ActivityCityShowController extends Controller
{
    // Voir CityShowController pour l'explication du contournement des
    // paramètres de route par `Request::route()`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowCityAction $showCity,
        ShowActivityAction $showActivity,
        ActivityCityPageQuery $pageQuery,
        BuildCityPathAction $buildCityPath,
        BuildActivityPathAction $buildActivityPath,
    ): ActivityCityResource {
        $city = $showCity->execute($resolvedCountry, (string) $request->route('citySlug'));
        $activity = $showActivity->execute($resolvedCountry, (string) $request->route('activitySlug'));

        $cached = $pageQuery->execute($city, $activity, $resolvedCountry);

        // Chemins montants pour le fil d'Ariane (Phase 13) — attributs
        // transitoires lus par CityResource / ActivityResource.
        $city->setAttribute('page_path', $buildCityPath->execute($city, $resolvedCountry));
        $activity->setAttribute('page_path', $buildActivityPath->execute($activity, $resolvedCountry));

        return ActivityCityResource::make(new ActivityCityPageData(
            city: $city,
            activity: $activity,
            path: $cached['path'],
            isIndexable: $cached['isIndexable'],
            blocks: $cached['blocks'],
            neighborCities: $cached['neighborCities'],
        ));
    }
}
