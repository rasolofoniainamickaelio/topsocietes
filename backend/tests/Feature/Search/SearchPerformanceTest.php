<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Search\Contracts\SearchEngineInterface;
use App\Domain\Search\Data\SearchCompaniesData;

/**
 * Objective le critère Trello Phase 14 ("recherche multi-critères sous
 * 200 ms") sur un volume représentatif — 3 000 lignes plutôt que les
 * "dizaines de milliers" citées dans la carte : suffisant pour que
 * PostgreSQL choisisse réellement ses index GIN (jamais un scan complet à
 * ce volume déjà), sans alourdir la suite de tests à chaque exécution.
 * Les entreprises générées n'ont pas de `city_id` (défaut de la factory) :
 * `CompanyObserver` ne déclenche donc aucun job de recalcul géo pour elles,
 * pas besoin de `withoutEvents()` (qui court-circuiterait aussi la
 * génération de `public_id` via `HasPublicId`).
 */
it('answers a multi-criteria search in under 200ms on a representative volume', function (): void {
    $country = Country::factory()->create();

    Company::factory()->for($country)->count(3000)->create();

    $target = Company::factory()->for($country)->create(['legal_name' => 'Keolis Lyon Perf']);

    $engine = app(SearchEngineInterface::class);

    $start = microtime(true);
    $results = $engine->searchCompanies($country, new SearchCompaniesData(term: 'Keolis Lyon'));
    $elapsedMs = (microtime(true) - $start) * 1000;

    expect(collect($results->items())->pluck('id'))->toContain($target->id)
        ->and($elapsedMs)->toBeLessThan(200);
})->group('performance');
