<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Geo\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Country
 */
class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'default_locale' => $this->default_locale,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'admin_level_labels' => $this->admin_level_labels,
            'url_patterns' => $this->url_patterns,
        ];
    }
}
