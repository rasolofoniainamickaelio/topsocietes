<?php

declare(strict_types=1);

namespace App\Enums;

enum RedirectReason: string
{
    case SlugChange = 'slug_change';
    case NameChange = 'name_change';
    case Merge = 'merge';
    case ArchitectureChange = 'architecture_change';
    case TaxonomyChange = 'taxonomy_change';
    case Migration = 'migration';
}
