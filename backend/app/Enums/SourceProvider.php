<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Fournisseur d'une source de données (table `sources`). Distinct de
 * `PoiProvider` : une source peut alimenter des faits sur n'importe quel
 * sujet (ville, quartier, activité, entreprise), pas seulement des POI.
 */
enum SourceProvider: string
{
    case OpenStreetMap = 'openstreetmap';
    case Wikidata = 'wikidata';
    case Wikipedia = 'wikipedia';
    case OpenData = 'opendata';
    case NationalRegistry = 'national_registry';
    case Manual = 'manual';
}
