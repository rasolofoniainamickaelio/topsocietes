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
            'children' => $this->whenLoaded('children', fn () => $this->children->map(fn ($child) => [
                'slug' => $child->slug,
                'name' => $child->name,
            ])->all()),
            'blocks' => ContentBlockResource::collection($this->whenLoaded('contents')),
        ];
    }
}
