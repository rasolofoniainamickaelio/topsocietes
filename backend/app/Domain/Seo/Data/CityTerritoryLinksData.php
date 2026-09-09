<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use Spatie\LaravelData\Data;

/**
 * Liens montants/descendants d'une page ville (Phase 13) — miroir réduit
 * de `CompanyLinksData` (Phase 15), pour une page territoriale plutôt
 * qu'une fiche entreprise. Un groupe absent (tableau vide) signifie qu'il
 * n'y a rien de pertinent à proposer, jamais une erreur (CLAUDE.md §6.5).
 *
 * Les quartiers ne figurent pas ici : `CityResource.districts` (existant,
 * `DistrictResource::collection`) les expose déjà avec slug et nom — les
 * dupliquer ici serait une donnée redondante et moins bien typée.
 */
class CityTerritoryLinksData extends Data
{
    /**
     * @param  array<int, InternalLinkData>  $activities
     */
    public function __construct(
        public readonly InternalLinkData $country,
        public readonly array $activities,
    ) {}
}
