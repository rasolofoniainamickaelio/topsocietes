<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Geo\Actions\ShowCityAction;
use App\Domain\Geo\Models\Country;
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
    public function __invoke(Country $resolvedCountry, Request $request, ShowCityAction $action): CityResource
    {
        return CityResource::make($action->execute($resolvedCountry, (string) $request->route('slug')));
    }
}
