<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PageRoute;
use Illuminate\Database\Eloquent\Model;

/**
 * Tient `routes` à jour pour une entité donnée : une ligne par
 * `(entity_type, entity_id)`, jamais dupliquée sur renommage (le chemin est
 * simplement réécrit sur la ligne existante — l'ancien chemin est déjà
 * couvert par sa propre redirection, `CreateRedirectAction`). Déclenche
 * systématiquement une réévaluation d'indexabilité (Phase 18) après
 * synchronisation.
 */
class SyncPageRouteAction
{
    public function __construct(private readonly EvaluatePagePublicationAction $evaluate) {}

    public function execute(Model $entity, Country $country, PageType $pageType, string $path): PageRoute
    {
        $route = PageRoute::updateOrCreate(
            ['entity_type' => $entity->getMorphClass(), 'entity_id' => $entity->getKey()],
            ['country_id' => $country->id, 'path' => $path, 'page_type' => $pageType, 'last_modified_at' => now()],
        );

        $this->evaluate->execute($route);

        return $route->fresh();
    }
}
