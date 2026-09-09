<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Content\Queries\ActivityContentBlocksQuery;
use App\Domain\Content\Queries\CityActivityContentBlocksQuery;
use App\Domain\Geo\Actions\ShowCityAction;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildActivityCityPathAction;
use App\Domain\Seo\Data\ActivityCityPageData;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Actions\ShowActivityAction;
use App\Http\Api\V1\Resources\ActivityCityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Page composite (Phase 12) : pas d'entité Eloquent unique, `ShowCityAction`
 * et `ShowActivityAction` (déjà utilisées par `CityShowController`/
 * `ActivityShowController`) fournissent chacune leur 404 propre — la
 * première levée gagne, comportement Laravel standard.
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
        CityActivityContentBlocksQuery $blocksQuery,
        ActivityContentBlocksQuery $activityBlocksQuery,
        BuildActivityCityPathAction $buildPath,
    ): ActivityCityResource {
        $city = $showCity->execute($resolvedCountry, (string) $request->route('citySlug'));
        $activity = $showActivity->execute($resolvedCountry, (string) $request->route('activitySlug'));

        $path = $buildPath->execute($city, $activity, $resolvedCountry);

        $neighborCities = $city->neighborLinks()
            ->with('neighborCity')
            ->get()
            ->map(fn ($link) => $link->neighborCity)
            ->filter()
            ->map(fn (City $neighbor) => [
                'slug' => $neighbor->slug,
                'name' => $neighbor->name,
                'path' => $buildPath->execute($neighbor, $activity, $resolvedCountry),
            ])
            ->values()
            ->all();

        $route = PageRoute::query()
            ->where('country_id', $resolvedCountry->id)
            ->where('path', $path)
            ->first();

        // Contenu croisé (spécifique à cette ville) suivi du contenu générique
        // du métier (identique sur toutes les villes, jamais régénéré ici —
        // même contenu qu'`ActivityShowController`, Phase 08/12).
        $blocks = $blocksQuery->execute($city, $activity)
            ->concat($activityBlocksQuery->forActivity($activity, $resolvedCountry));

        return ActivityCityResource::make(new ActivityCityPageData(
            city: $city,
            activity: $activity,
            path: $path,
            isIndexable: $route === null || $route->is_indexable,
            blocks: $blocks,
            neighborCities: $neighborCities,
        ));
    }
}
