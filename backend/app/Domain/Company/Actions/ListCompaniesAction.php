<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Data\ListCompaniesData;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * Pagination par curseur, jamais par offset (CLAUDE.md §6, Phase 14 :
 * "pas de LIKE %…% ni d'OFFSET sur des millions de lignes") — cette liste
 * publique est exposée sans limite de profondeur, contrairement à un
 * listing d'admin.
 */
class ListCompaniesAction
{
    private const PER_PAGE = 20;

    /** @return CursorPaginator<int, Company> */
    public function execute(Country $country, ListCompaniesData $data): CursorPaginator
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
            ->orderBy('id')
            ->cursorPaginate($data->per_page ?: self::PER_PAGE, ['*'], 'cursor', $data->cursor);
    }
}
