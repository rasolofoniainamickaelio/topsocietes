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
     * @return array<string, mixed>
     */
    public function execute(Country $country, string $path): array
    {
        $redirect = Redirect::query()
            ->where('country_id', $country->id)
            ->where('from_path', $path)
            ->where('is_active', true)
            ->first();

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
}
