<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Billing\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'features' => $this->features,
        ];
    }
}
