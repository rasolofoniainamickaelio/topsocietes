<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Ads\Models\ServiceLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceLink
 */
class ServiceLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'group' => $this->group,
            'label' => $this->label,
            'url' => $this->url,
        ];
    }
}
