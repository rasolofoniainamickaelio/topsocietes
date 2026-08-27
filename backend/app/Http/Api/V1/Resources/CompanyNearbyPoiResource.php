<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Company\Models\CompanyNearbyPoi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CompanyNearbyPoi
 */
class CompanyNearbyPoiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->poi->name,
            'category' => $this->poi->category,
            'distance_m' => $this->distance_m,
        ];
    }
}
