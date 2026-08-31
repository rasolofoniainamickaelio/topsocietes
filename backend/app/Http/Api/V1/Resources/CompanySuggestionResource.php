<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Search\Data\CompanySuggestionData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CompanySuggestionData
 */
class CompanySuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'legal_name' => $this->legalName,
            'city' => $this->cityName,
        ];
    }
}
