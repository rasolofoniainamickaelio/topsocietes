<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Geo\Models\District;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin District
 */
class DistrictResource extends JsonResource
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
            'companies_count' => $this->companies_count,
            'has_local_content' => $this->has_local_content,
            'blocks' => ContentBlockResource::collection($this->whenLoaded('contents')),
        ];
    }
}
