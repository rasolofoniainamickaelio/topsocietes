<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Geo\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sert à la fois la liste et le détail : `districts`/`neighbors`/`blocks`
 * n'apparaissent que si la relation correspondante a été chargée
 * (`whenLoaded`) — la liste ne les charge pas, le détail oui.
 *
 * @mixin City
 */
class CityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'population' => $this->population,
            'area_km2' => $this->area_km2,
            'companies_count' => $this->companies_count,
            'has_local_content' => $this->has_local_content,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'districts' => DistrictResource::collection($this->whenLoaded('districts')),
            'neighbors' => CityNeighborResource::collection($this->whenLoaded('neighborLinks')),
            'blocks' => ContentBlockResource::collection($this->whenLoaded('contents')),
            // Département + région, pour le fil d'Ariane (Phase 13) — texte
            // simple côté frontend, ces deux niveaux n'ont pas encore de
            // page à cibler.
            'admin_division' => $this->whenLoaded('adminDivision', fn () => $this->adminDivision === null ? null : [
                'name' => $this->adminDivision->name,
                'region' => $this->adminDivision->relationLoaded('parent') ? $this->adminDivision->parent?->name : null,
            ]),
            // Attributs transitoires posés par `CityShowController` (Phase 13),
            // même patron que `CompanyResource` (Phase 15/16/18).
            ...($this->offsetExists('page_path')
                ? ['path' => $this->resource->getAttribute('page_path')]
                : []),
            ...($this->offsetExists('page_links')
                ? ['links' => $this->resource->getAttribute('page_links')]
                : []),
            ...($this->offsetExists('page_route') && $this->resource->getAttribute('page_route') !== null
                ? ['is_indexable' => $this->resource->getAttribute('page_route')->is_indexable]
                : []),
        ];
    }
}
