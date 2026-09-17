<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'code' => $this->code,
            'label' => $this->public_label,
            'level' => $this->level,
            'companies_count' => $this->companies_count,
            'sectors' => $this->whenLoaded('sectors', fn () => $this->sectors->map(fn ($sector) => [
                'slug' => $sector->slug,
                'name' => $sector->name,
            ])->all()),
            'blocks' => ContentBlockResource::collection($this->whenLoaded('contents')),
            // Attributs transitoires posés par `ActivityShowController`
            // (Phase 13), même patron que `CityResource`.
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
