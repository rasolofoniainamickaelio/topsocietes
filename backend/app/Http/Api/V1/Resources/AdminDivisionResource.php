<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Geo\Models\AdminDivision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdminDivision
 */
class AdminDivisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'level' => $this->level,
            'population' => $this->population,
            'companies_count' => $this->companies_count,
            'parent' => $this->whenLoaded('parent', fn () => $this->parent === null ? null : [
                'slug' => $this->parent->slug,
                'name' => $this->parent->name,
            ]),
            'children' => $this->whenLoaded('children', fn () => $this->children->map(fn ($child) => [
                'slug' => $child->slug,
                'name' => $child->name,
            ])->all()),
            'blocks' => ContentBlockResource::collection($this->whenLoaded('contents')),
            // Attributs transitoires posés par `AdminDivisionShowController`
            // (Phase 13), même patron que `CityResource`.
            ...($this->offsetExists('page_path')
                ? ['path' => $this->resource->getAttribute('page_path')]
                : []),
            ...($this->offsetExists('page_links')
                ? ['links' => $this->resource->getAttribute('page_links')]
                : []),
            ...($this->offsetExists('page_level_label')
                ? ['level_label' => $this->resource->getAttribute('page_level_label')]
                : []),
            ...($this->offsetExists('page_route') && $this->resource->getAttribute('page_route') !== null
                ? ['is_indexable' => $this->resource->getAttribute('page_route')->is_indexable]
                : []),
        ];
    }
}
