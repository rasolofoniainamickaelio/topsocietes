<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Activity;

/**
 * Miroir de `SyncPageRouteAction`, pour une page composite (Phase 12) sans
 * entité Eloquent unique : `entity_type`/`entity_id` restent `null`
 * (décision actée dès la migration `routes`), la ligne se retrouve donc par
 * `(country_id, path)` plutôt que par entité.
 */
class SyncActivityCityPageRouteAction
{
    public function __construct(
        private readonly EvaluatePagePublicationAction $evaluate,
        private readonly BuildActivityCityPathAction $buildPath,
    ) {}

    public function execute(City $city, Activity $activity, Country $country): PageRoute
    {
        $path = $this->buildPath->execute($city, $activity, $country);

        $route = PageRoute::updateOrCreate(
            ['country_id' => $country->id, 'path' => $path],
            ['entity_type' => null, 'entity_id' => null, 'page_type' => PageType::ActivityCity, 'last_modified_at' => now()],
        );

        $this->evaluate->execute($route);

        return $route->fresh();
    }
}
