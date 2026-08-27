<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Company\Models\Establishment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Establishment
 */
class EstablishmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'street_number' => $this->street_number,
            'street_name' => $this->street_name,
            'address_line2' => $this->address_line2,
            'postal_code' => $this->postal_code,
            'is_headquarters' => $this->is_headquarters,
        ];
    }
}
