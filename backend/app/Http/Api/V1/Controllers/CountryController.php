<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Http\Api\V1\Resources\CountryResource;
use App\Http\Controllers\Controller;
use App\Models\Country;

class CountryController extends Controller
{
    // Nommé différemment du paramètre de route `{country}` (string) pour
    // éviter que Laravel ne tente un binding implicite de modèle dessus :
    // cette instance vient du conteneur (ResolveCountry::class), pas d'une
    // résolution automatique par clé de route.
    public function __invoke(Country $resolvedCountry): CountryResource
    {
        return CountryResource::make($resolvedCountry);
    }
}
