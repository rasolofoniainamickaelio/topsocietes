<?php

declare(strict_types=1);

namespace App\Domain\Search\Contracts;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Search\Data\CompanySuggestionData;
use App\Domain\Search\Data\SearchCompaniesData;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Collection;

/**
 * Isole le Domain du moteur de recherche technique (ADR 0001) : si le
 * volume ou les besoins de pertinence l'exigent plus tard (Meilisearch,
 * Typesense), seule l'implémentation liée dans `AppServiceProvider`
 * change, jamais le code appelant (contrôleurs, Actions).
 */
interface SearchEngineInterface
{
    /** @return CursorPaginator<int, Company> */
    public function searchCompanies(Country $country, SearchCompaniesData $data): CursorPaginator;

    /**
     * @return Collection<int, CompanySuggestionData>
     */
    public function autocomplete(Country $country, string $term, int $limit = 10): Collection;
}
