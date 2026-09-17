<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildCountryPathAction;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Services\TerritoryLinkingService;
use App\Http\Api\V1\Resources\CountryResource;
use App\Http\Controllers\Controller;

class CountryController extends Controller
{
    // Nommé différemment du paramètre de route `{country}` (string) pour
    // éviter que Laravel ne tente un binding implicite de modèle dessus :
    // cette instance vient du conteneur (ResolveCountry::class), pas d'une
    // résolution automatique par clé de route.
    public function __invoke(
        Country $resolvedCountry,
        BuildCountryPathAction $buildPath,
        TerritoryLinkingService $linkingService,
    ): CountryResource {
        // Attributs transitoires, jamais persistés : même patron que
        // `CityShowController` (Phase 13).
        $resolvedCountry->setAttribute('page_path', $buildPath->execute());
        $resolvedCountry->setAttribute('page_links', $linkingService->forCountry($resolvedCountry));
        $resolvedCountry->setAttribute('page_route', PageRoute::query()
            ->where('entity_type', $resolvedCountry->getMorphClass())
            ->where('entity_id', $resolvedCountry->id)
            ->where('page_type', PageType::Country)
            ->first());

        return CountryResource::make($resolvedCountry);
    }
}
