<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Geo\Data\ListCitiesData;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCitiesAction
{
    private const PER_PAGE = 20;

    /** @return LengthAwarePaginator<int, City> */
    public function execute(Country $country, ListCitiesData $data): LengthAwarePaginator
    {
        return City::query()
            ->where('country_id', $country->id)
            ->when(
                filled($data->search),
                fn ($query) => $query->whereRaw(
                    "search_vector @@ plainto_tsquery('french_unaccent', ?)",
                    [$data->search],
                ),
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE);
    }
}
