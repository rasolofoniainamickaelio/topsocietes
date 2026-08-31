<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;
use App\Domain\Search\Contracts\SearchEngineInterface;
use App\Domain\Search\Data\CompanySuggestionData;
use App\Domain\Search\Data\SearchCompaniesData;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation PostgreSQL de `SearchEngineInterface` (Phase 14,
 * Meilisearch explicitement hors périmètre). Un terme purement numérique
 * est traité comme une recherche SIREN (exact/préfixe), jamais floue ; un
 * terme texte combine `tsvector` (rapide, radical) et un repli `pg_trgm`
 * (tolérance aux fautes, équivalent Postgres-only de ce qu'apporterait
 * Meilisearch).
 */
class PostgresSearchEngine implements SearchEngineInterface
{
    private const TRIGRAM_THRESHOLD = 0.2;

    /** @return CursorPaginator<int, Company> */
    public function searchCompanies(Country $country, SearchCompaniesData $data): CursorPaginator
    {
        $base = $this->applyFilters(
            Company::query()->where('companies.country_id', $country->id),
            $data,
        );

        $term = $data->term !== null ? trim($data->term) : null;

        if (blank($term)) {
            return $base->with(['city', 'district', 'activity'])
                ->orderBy('legal_name')
                ->orderBy('id')
                ->cursorPaginate($data->per_page, ['*'], 'cursor', $data->cursor);
        }

        if (preg_match('/^\d+$/', $term) === 1) {
            return $base->where('national_id', 'like', "{$term}%")
                ->with(['city', 'district', 'activity'])
                ->orderBy('national_id')
                ->orderBy('id')
                ->cursorPaginate($data->per_page, ['*'], 'cursor', $data->cursor);
        }

        $this->setTrigramThreshold();

        $ranked = $base
            ->selectRaw(
                "companies.*, GREATEST(ts_rank_cd(search_vector, plainto_tsquery('french_unaccent', ?)), similarity(legal_name, ?)) as rank",
                [$term, $term],
            )
            ->where(fn (Builder $query) => $query
                ->whereRaw("search_vector @@ plainto_tsquery('french_unaccent', ?)", [$term])
                ->orWhereRaw('legal_name % ?', [$term]));

        // `rank` est un alias de SELECT : inutilisable dans le WHERE que
        // `cursorPaginate()` génère pour la page suivante. En passant par
        // `fromSub()`, il devient une colonne réelle de la requête externe,
        // donc exploitable par le curseur (décision 5 du plan).
        return Company::query()
            ->fromSub($ranked, 'companies')
            ->with(['city', 'district', 'activity'])
            ->orderByDesc('rank')
            ->orderByDesc('id')
            ->cursorPaginate($data->per_page, ['*'], 'cursor', $data->cursor);
    }

    /** @return Collection<int, CompanySuggestionData> */
    public function autocomplete(Country $country, string $term, int $limit = 10): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return new Collection;
        }

        $this->setTrigramThreshold();

        return Company::query()
            ->where('country_id', $country->id)
            ->selectRaw(
                "companies.*, GREATEST(ts_rank_cd(search_vector, plainto_tsquery('french_unaccent', ?)), similarity(legal_name, ?)) as rank",
                [$term, $term],
            )
            ->where(fn (Builder $query) => $query
                ->whereRaw("search_vector @@ plainto_tsquery('french_unaccent', ?)", [$term])
                ->orWhereRaw('legal_name % ?', [$term]))
            ->with('city')
            ->orderByDesc('rank')
            ->limit($limit)
            ->get()
            ->map(fn (Company $company) => new CompanySuggestionData(
                slug: $company->slug,
                legalName: $company->legal_name,
                cityName: $company->city?->name,
            ));
    }

    /**
     * L'opérateur `%` (seul indexable par `gin_companies_name_trgm`) lit son
     * seuil dans ce paramètre de session — `similarity(a, b) > x` en WHERE
     * ne l'est pas, malgré l'index GIN existant, car pg_trgm n'indexe que
     * `%`/`<->`, jamais un appel de fonction arbitraire.
     */
    private function setTrigramThreshold(): void
    {
        DB::statement('SET pg_trgm.similarity_threshold = '.self::TRIGRAM_THRESHOLD);
    }

    /**
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    private function applyFilters(Builder $query, SearchCompaniesData $data): Builder
    {
        return $query
            ->when(
                filled($data->city),
                fn ($q) => $q->whereHas('city', fn ($q2) => $q2->where('slug', $data->city)),
            )
            ->when(
                filled($data->postal_code),
                fn ($q) => $q->whereHas('city', fn ($q2) => $q2->whereRaw('? = ANY(postal_codes)', [$data->postal_code])),
            )
            ->when(
                filled($data->activity),
                fn ($q) => $q->whereHas('activity', fn ($q2) => $q2->where('slug', $data->activity)),
            )
            ->when(
                filled($data->sector),
                fn ($q) => $q->whereHas('activity.sectors', fn ($q2) => $q2->where('slug', $data->sector)),
            )
            ->when(filled($data->admin_division), function ($q) use ($data): void {
                $division = AdminDivision::query()->where('slug', $data->admin_division)->first();

                if ($division === null) {
                    // Slug de division inconnu : aucun résultat, jamais
                    // "toute la base" par défaut.
                    $q->whereRaw('1 = 0');

                    return;
                }

                // Hiérarchie à 2 niveaux (région → département) : le
                // parent direct suffit à couvrir les descendants, pas
                // besoin de remonter `path` (jamais peuplé) ni de CTE
                // récursive.
                $q->whereIn('admin_division_id', function ($sub) use ($division): void {
                    $sub->select('id')
                        ->from('admin_divisions')
                        ->where('id', $division->id)
                        ->orWhere('parent_id', $division->id);
                });
            });
    }
}
