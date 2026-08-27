<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Data\ListCompaniesData;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCompaniesAction
{
    private const PER_PAGE = 20;

    /** @return LengthAwarePaginator<int, Company> */
    public function execute(Country $country, ListCompaniesData $data): LengthAwarePaginator
    {
        return Company::query()
            ->where('country_id', $country->id)
            ->when(
                filled($data->search),
                fn ($query) => $query->whereRaw(
                    "search_vector @@ plainto_tsquery('french_unaccent', ?)",
                    [$data->search],
                ),
            )
            ->when(
                filled($data->city),
                fn ($query) => $query->whereHas('city', fn ($q) => $q->where('slug', $data->city)),
            )
            ->when(
                filled($data->activity),
                fn ($query) => $query->whereHas('activity', fn ($q) => $q->where('slug', $data->activity)),
            )
            ->with(['city', 'district', 'activity'])
            ->orderBy('legal_name')
            ->paginate(self::PER_PAGE);
    }
}
