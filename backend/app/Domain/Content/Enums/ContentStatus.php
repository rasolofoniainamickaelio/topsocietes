<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

/**
 * Workflow éditorial des contenus mutualisés (ville/quartier/activité/
 * croisé). Distinct de `CompanyContentStatus` (pipeline propre à une
 * fiche entreprise).
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Review = 'review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Stale = 'stale';
}
