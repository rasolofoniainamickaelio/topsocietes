<?php

declare(strict_types=1);

namespace App\Domain\Search\Data;

use Spatie\LaravelData\Data;

/**
 * Résultat léger d'autocomplétion — pas les champs complets d'une fiche
 * (CLAUDE.md §6.3 : les contacts ne sont de toute façon jamais dans une
 * réponse de liste), juste de quoi afficher une suggestion et y lier.
 */
class CompanySuggestionData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $legalName,
        public readonly ?string $cityName,
    ) {}
}
