<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Actions;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Database\Eloquent\Collection;

class ListActivitiesAction
{
    /** @return Collection<int, Activity> */
    public function execute(Country $country): Collection
    {
        return Activity::query()
            ->whereHas('nomenclature', fn ($query) => $query->where('country_id', $country->id))
            ->where('is_publishable', true)
            ->orderBy('code')
            ->get();
    }
}
