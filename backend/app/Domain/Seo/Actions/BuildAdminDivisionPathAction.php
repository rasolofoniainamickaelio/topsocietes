<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;

/**
 * Un seul jeu de fichiers sert les deux niveaux (région, département/
 * province/...) : le gabarit est choisi par `division.level`
 * (`countries.url_patterns.admin_division_{level}`, CLAUDE.md §6.6 — jamais
 * une structure codée en dur). Un pays qui n'a pas encore ce niveau de
 * division (aucune donnée seedée) ou pas de gabarit dédié retombe sur un
 * motif générique par défaut plutôt que d'échouer.
 */
class BuildAdminDivisionPathAction
{
    private const DEFAULT_PATTERN = '/territoire/{admin_division}';

    public function execute(AdminDivision $division, Country $country): string
    {
        $urlPatterns = $country->url_patterns ?? [];
        $key = "admin_division_{$division->level}";
        $pattern = is_string($urlPatterns[$key] ?? null) ? $urlPatterns[$key] : self::DEFAULT_PATTERN;

        return strtr($pattern, ['{admin_division}' => $division->slug]);
    }
}
