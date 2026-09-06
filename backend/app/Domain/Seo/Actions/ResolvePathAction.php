<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Seo\Models\Redirect;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Résolveur central (docs/DATABASE.md §4-I) : une redirection active prime
 * toujours sur une route, jamais les deux en même temps pour un même
 * chemin.
 */
class ResolvePathAction
{
    /**
     * `CreateRedirectAction` ne produit jamais de chaîne, mais une
     * redirection créée à la main en back-office le pourrait — cette limite
     * de sauts est une garde défensive, jamais atteinte en usage normal.
     */
    private const MAX_CHAIN_HOPS = 5;

    /**
     * @return array<string, mixed>
     */
    public function execute(Country $country, string $path): array
    {
        $redirect = $this->resolveActiveRedirect($country, $path);

        if ($redirect !== null) {
            return [
                'type' => 'redirect',
                'to' => $redirect->to_path,
                'status_code' => $redirect->status_code,
            ];
        }

        $route = PageRoute::query()
            ->where('country_id', $country->id)
            ->where('path', $path)
            ->first();

        if ($route !== null) {
            return [
                'type' => 'route',
                'page_type' => $route->page_type->value,
                'entity_type' => $route->entity_type,
                'entity_id' => $route->entity_id,
                'is_indexable' => $route->is_indexable,
            ];
        }

        throw new ModelNotFoundException;
    }

    /**
     * Suit une éventuelle chaîne jusqu'à sa cible finale sans jamais
     * persister de changement ici (`CreateRedirectAction` est seul
     * responsable d'aplatir les chaînes en base) — seule la représentation
     * en mémoire de la redirection trouvée est réécrite vers cette cible.
     */
    private function resolveActiveRedirect(Country $country, string $path, int $hops = 0): ?Redirect
    {
        if ($hops >= self::MAX_CHAIN_HOPS) {
            return null;
        }

        $redirect = Redirect::query()
            ->where('country_id', $country->id)
            ->where('from_path', $path)
            ->where('is_active', true)
            ->first();

        if ($redirect === null) {
            return null;
        }

        $next = $this->resolveActiveRedirect($country, $redirect->to_path, $hops + 1);

        if ($next !== null) {
            $redirect->to_path = $next->to_path;
        }

        return $redirect;
    }
}
