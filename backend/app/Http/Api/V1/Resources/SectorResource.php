<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sector
 */
class SectorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'companies_count' => $this->companies_count,
            'blocks' => ContentBlockResource::collection($this->whenLoaded('contents')),
        ];
    }
}
