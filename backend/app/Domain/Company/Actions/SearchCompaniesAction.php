<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Search\Contracts\SearchEngineInterface;
use App\Domain\Search\Data\CompanySuggestionData;
use App\Domain\Search\Data\SearchCompaniesData;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Collection;

/**
 * Le contrôleur ne connaît que cette Action, jamais `SearchEngineInterface`
 * directement (CLAUDE.md §3) — cohérent avec le reste du Domain Company.
 */
class SearchCompaniesAction
{
    public function __construct(private readonly SearchEngineInterface $searchEngine) {}

    /** @return CursorPaginator<int, Company> */
    public function execute(Country $country, SearchCompaniesData $data): CursorPaginator
    {
        return $this->searchEngine->searchCompanies($country, $data);
    }

    /** @return Collection<int, CompanySuggestionData> */
    public function autocomplete(Country $country, string $term): Collection
    {
        return $this->searchEngine->autocomplete($country, $term);
    }
}
