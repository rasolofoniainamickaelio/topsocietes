<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Geo\Data\ListAdminDivisionsData;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;
use Illuminate\Pagination\LengthAwarePaginator;

class ListAdminDivisionsAction
{
    private const PER_PAGE = 50;

    /** @return LengthAwarePaginator<int, AdminDivision> */
    public function execute(Country $country, ListAdminDivisionsData $data): LengthAwarePaginator
    {
        return AdminDivision::query()
            ->where('country_id', $country->id)
            ->when($data->level !== null, fn ($query) => $query->where('level', $data->level))
            ->when(
                filled($data->parent),
                fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('slug', $data->parent)),
            )
            ->orderBy('name')
            ->paginate(self::PER_PAGE);
    }
}
