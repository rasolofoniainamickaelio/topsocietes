<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Plan;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Eloquent\Collection;

class ListActivePlansAction
{
    /** @return Collection<int, Plan> */
    public function execute(Country $country): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('country_id')->orWhere('country_id', $country->id))
            ->orderBy('price_cents')
            ->get();
    }
}
