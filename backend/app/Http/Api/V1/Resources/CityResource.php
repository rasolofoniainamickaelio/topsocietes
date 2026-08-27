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
        ];
    }
}
