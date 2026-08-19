<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * État du pipeline d'enrichissement propre à une fiche entreprise
 * (`companies.content_status`). Distinct de `ContentStatus` (workflow
 * éditorial des contenus mutualisés ville/quartier/activité) : les deux
 * concepts portent le même nom générique dans le prompt Phase 2 mais ont
 * des jeux de valeurs différents et ne doivent pas partager un seul enum.
 */
enum CompanyContentStatus: string
{
    case Pending = 'pending';
    case Enriched = 'enriched';
    case Published = 'published';
    case Excluded = 'excluded';
}
