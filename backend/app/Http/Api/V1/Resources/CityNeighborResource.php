<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Geo\Models\CityNeighbor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CityNeighbor
 */
class CityNeighborResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->neighborCity->slug,
            'name' => $this->neighborCity->name,
            'distance_m' => $this->distance_m,
        ];
    }
}
