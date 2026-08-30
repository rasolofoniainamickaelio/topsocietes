<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Geo\Models\AdminDivision;
use Illuminate\Support\Collection;

/**
 * Remonte la chaîne `AdminDivision::parent` (département → région → …) à
 * la recherche des sections encore manquantes, en s'arrêtant dès que
 * toutes sont trouvées ou que la chaîne est épuisée. Une section trouvée
 * à un niveau n'est plus cherchée au niveau suivant (Phase 08 : le repli
 * ne recouvre jamais un contenu déjà présent).
 */
class ResolveAdminDivisionContentQuery
{
    /**
     * @param  array<int, string>  $missingSections
     * @return Collection<int, AdminDivisionContent>
     */
    public function execute(?AdminDivision $startingDivision, array $missingSections): Collection
    {
        $found = collect();
        $remaining = $missingSections;
        $division = $startingDivision;

        while ($division !== null && $remaining !== []) {
            $rows = AdminDivisionContent::query()
                ->where('admin_division_id', $division->id)
                ->where('status', ContentStatus::Published)
                ->whereIn('section', $remaining)
                ->get();

            foreach ($rows as $row) {
                $found->push($row);
                $remaining = array_values(array_diff($remaining, [$row->section]));
            }

            $division = $division->parent;
        }

        return $found;
    }
}
