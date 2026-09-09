<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Seo\Data\ActivityCityPageData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `city_activity_contents` n'a pas de colonne `title` (contrairement à
 * `activity_contents`) : `ContentBlockResource` la lira toujours `null`
 * pour ces blocs — le titre d'affichage est composé côté frontend à partir
 * de la section et du couple (ville, activité) déjà connu de la page.
 *
 * @mixin ActivityCityPageData
 */
class ActivityCityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ActivityCityPageData $data */
        $data = $this->resource;

        return [
            'city' => CityResource::make($data->city),
            'activity' => ActivityResource::make($data->activity),
            'path' => $data->path,
            'is_indexable' => $data->isIndexable,
            'blocks' => ContentBlockResource::collection($data->blocks),
            'neighbor_cities' => $data->neighborCities,
        ];
    }
}
