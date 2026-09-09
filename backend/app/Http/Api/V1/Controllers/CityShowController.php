<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Actions\ShowCityAction;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildCityPathAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Http\Api\V1\Resources\CityResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CityShowController extends Controller
{
    // `$slug` n'est pas un paramètre de méthode : combiné à un paramètre
    // injecté par le conteneur dont le nom ne correspond à aucun segment de
    // route (`$resolvedCountry`, voir CountryController), Laravel désaligne
    // la résolution positionnelle des paramètres de route restants et lie
    // `$slug` à la valeur de `{country}` au lieu de `{slug}`.
    public function __invoke(
        Country $resolvedCountry,
        Request $request,
        ShowCityAction $action,
        BuildCityPathAction $buildPath,
        TerritoryLinkingService $linkingService,
    ): CityResource {
        $city = $action->execute($resolvedCountry, (string) $request->route('slug'));

        // Attributs transitoires, jamais persistés : même patron que
        // `CompanyShowController` (Phase 16/15/18).
        $city->setAttribute('page_path', $buildPath->execute($city, $resolvedCountry));
        $city->setAttribute('page_links', $linkingService->forCity($city, $resolvedCountry));
        $city->setAttribute('page_route', PageRoute::query()
            ->where('entity_type', $city->getMorphClass())
            ->where('entity_id', $city->id)
            ->where('page_type', PageType::City)
            ->first());

        return CityResource::make($city);
    }
}
