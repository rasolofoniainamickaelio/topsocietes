<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Content\Queries\CityContentBlocksQuery;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowCityAction
{
    public function __construct(private readonly CityContentBlocksQuery $contentBlocksQuery) {}

    public function execute(Country $country, string $slug): City
    {
        $city = City::query()
            ->where('country_id', $country->id)
            ->where('slug', $slug)
            ->with(['districts', 'neighborLinks.neighborCity', 'adminDivision.parent'])
            ->first();

        if ($city === null) {
            throw new ModelNotFoundException;
        }

        // Le repli vers la division administrative (Phase 08) mêle des
        // instances de modèles différents dans la même collection : elle
        // est réinjectée sur la relation `contents` pour que `CityResource`
        // continue de la lire via `whenLoaded('contents')` sans changement.
        $city->setRelation('contents', $this->contentBlocksQuery->execute($city));

        return $city;
    }
}
