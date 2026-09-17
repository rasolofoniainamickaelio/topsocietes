<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Sector;

/**
 * `Sector` est la seule entité de `routes` à avoir PLUSIEURS routes pour un
 * même `entity_id` — une par pays actif, puisqu'un secteur est transversal
 * (pas rattaché à un pays) mais que sa page l'est. La `SyncPageRouteAction`
 * générique ne convient donc pas : sa clé de correspondance
 * (`entity_type`, `entity_id`) écraserait la route d'un pays avec celle
 * d'un autre. Ici la clé inclut `country_id`.
 */
class SyncSectorPageRouteAction
{
    public function __construct(
        private readonly EvaluatePagePublicationAction $evaluate,
        private readonly BuildSectorPathAction $buildPath,
    ) {}

    public function execute(Sector $sector, Country $country): PageRoute
    {
        $route = PageRoute::updateOrCreate(
            ['country_id' => $country->id, 'entity_type' => $sector->getMorphClass(), 'entity_id' => $sector->id],
            ['path' => $this->buildPath->execute($sector, $country), 'page_type' => PageType::Sector, 'last_modified_at' => now()],
        );

        $this->evaluate->execute($route);

        return $route->fresh();
    }
}
