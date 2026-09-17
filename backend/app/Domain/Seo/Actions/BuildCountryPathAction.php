<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

/**
 * La page pays est toujours la racine du sous-domaine — contrairement aux
 * autres types de page, aucun gabarit `url_patterns` n'est nécessaire ni
 * possible (CLAUDE.md §6.6 : piloté par la table `countries`, mais ici la
 * règle elle-même est universelle, pas une variation par pays).
 */
class BuildCountryPathAction
{
    public function execute(): string
    {
        return '/';
    }
}
