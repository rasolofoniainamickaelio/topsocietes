<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Actions;

use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Database\Eloquent\Collection;

/**
 * Les secteurs sont un regroupement éditorial transversal, indépendant des
 * nomenclatures nationales (docs/DATABASE.md §4-B) — jamais scopés par pays,
 * contrairement aux activités.
 */
class ListSectorsAction
{
    /** @return Collection<int, Sector> */
    public function execute(): Collection
    {
        return Sector::query()->orderBy('name')->get();
    }
}
