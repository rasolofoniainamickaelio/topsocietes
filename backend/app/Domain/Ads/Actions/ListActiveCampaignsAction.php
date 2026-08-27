<?php

declare(strict_types=1);

namespace App\Domain\Ads\Actions;

use App\Domain\Ads\Models\AdCampaign;
use App\Domain\Ads\Models\AdSlot;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ListActiveCampaignsAction
{
    /** @return Collection<int, AdCampaign> */
    public function execute(Country $country, string $slotCode): Collection
    {
        // AdSlot est un emplacement global (non scopé pays) ; seule
        // AdCampaign.country_id restreint une campagne à un pays donné.
        $slot = AdSlot::query()->where('code', $slotCode)->first();

        if ($slot === null) {
            throw new ModelNotFoundException;
        }

        $now = now();

        return AdCampaign::query()
            ->where('slot_id', $slot->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('country_id')->orWhere('country_id', $country->id))
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderByDesc('weight')
            ->get();
    }
}
