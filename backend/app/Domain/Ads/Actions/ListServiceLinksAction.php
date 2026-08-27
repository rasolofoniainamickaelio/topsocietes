<?php

declare(strict_types=1);

namespace App\Domain\Ads\Actions;

use App\Domain\Ads\Models\ServiceLink;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Collection;

class ListServiceLinksAction
{
    /** @return Collection<int, ServiceLink> */
    public function execute(Country $country): Collection
    {
        return ServiceLink::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('country_id')->orWhere('country_id', $country->id))
            ->orderBy('group')
            ->orderBy('display_order')
            ->get();
    }
}
