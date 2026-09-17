<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use Spatie\LaravelData\Data;

/**
 * Miroir de `CityTerritoryLinksData` (Phase 13) pour une division
 * administrative (région ou département/province/...) : un lien montant
 * (parent, ou pays si pas de parent / pas encore de route) et des liens
 * descendants (sous-divisions pour une région, communes pour un
 * département — seulement celles déjà synchronisées).
 */
class AdminDivisionTerritoryLinksData extends Data
{
    /** @param array<int, InternalLinkData> $children */
    public function __construct(
        public readonly InternalLinkData $parent,
        public readonly array $children,
    ) {}
}
