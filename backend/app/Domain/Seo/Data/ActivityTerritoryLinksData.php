<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use Spatie\LaravelData\Data;

/**
 * Liens de la page activité seule (Phase 13) : lien montant vers l'activité
 * parente (si elle en a une et que sa route existe déjà, sinon absent — pas
 * de repli sur le pays, contrairement à une division administrative : une
 * activité sans parent n'a pas de "page racine" à cibler), liens vers les
 * secteurs qui la regroupent et vers les villes où elle est déjà
 * pratiquée (pages activité×ville, Phase 12).
 */
class ActivityTerritoryLinksData extends Data
{
    /**
     * @param  array<int, InternalLinkData>  $sectors
     * @param  array<int, InternalLinkData>  $cities
     */
    public function __construct(
        public readonly ?InternalLinkData $parent,
        public readonly array $sectors,
        public readonly array $cities,
    ) {}
}
