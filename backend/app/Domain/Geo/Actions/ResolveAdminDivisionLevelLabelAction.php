<?php

declare(strict_types=1);

namespace App\Domain\Geo\Actions;

use App\Domain\Geo\Models\Country;

/**
 * Résout le libellé local d'un niveau de division administrative (« Région »,
 * « Province », « Préfecture », « Wilaya »...) depuis `countries.admin_level_labels`
 * — jamais un mot codé en dur par pays (CLAUDE.md §6.6). Les clés de ce champ
 * portent le nom local exact (`region`, `province`, `prefecture`...), pas le
 * niveau numérique : par convention (respectée par tous les seeders pays),
 * la clé `city` est toujours en dernière position et les niveaux
 * administratifs précèdent dans l'ordre (niveau 1 puis niveau 2).
 */
class ResolveAdminDivisionLevelLabelAction
{
    public function execute(Country $country, int $level): ?string
    {
        $labels = $country->admin_level_labels ?? [];
        $position = 0;

        foreach ($labels as $key => $label) {
            if ($key === 'city') {
                continue;
            }

            $position++;

            if ($position === $level) {
                return $label;
            }
        }

        return null;
    }
}
