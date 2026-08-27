<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Ads\Models\AdCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdCampaign
 */
class AdCampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'theme' => $this->theme,
        ];
    }
}
