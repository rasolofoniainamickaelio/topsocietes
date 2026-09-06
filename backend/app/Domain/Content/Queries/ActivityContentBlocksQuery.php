<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Collection;

/**
 * Contenu d'activité/secteur : pas de hiérarchie géographique ici, mais un
 * repli générique ↔ spécifique par pays (`activity_contents.country_id`
 * nullable). Une ligne spécifique au pays prime toujours sur la ligne
 * générique pour une même section (Phase 08).
 */
class ActivityContentBlocksQuery
{
    /** @return Collection<int, ActivityContent> */
    public function forActivity(Activity $activity, Country $country): Collection
    {
        return $this->dedupe(
            ActivityContent::query()
                ->where('activity_id', $activity->id)
                ->where('status', ContentStatus::Published)
                ->where(fn ($query) => $query->whereNull('country_id')->orWhere('country_id', $country->id))
                ->get(),
            $country,
        );
    }

    /** @return Collection<int, ActivityContent> */
    public function forSector(Sector $sector, Country $country): Collection
    {
        return $this->dedupe(
            ActivityContent::query()
                ->where('sector_id', $sector->id)
                ->where('status', ContentStatus::Published)
                ->where(fn ($query) => $query->whereNull('country_id')->orWhere('country_id', $country->id))
                ->get(),
            $country,
        );
    }

    /**
     * Même résultat qu'appeler `forSector()` pour chacun des secteurs, en
     * une seule requête (Phase 20) — une activité peut appartenir à
     * plusieurs secteurs, jamais une requête par secteur sur une page qui
     * les affiche tous.
     *
     * @param  Collection<int, Sector>  $sectors
     * @return Collection<int, ActivityContent>
     */
    public function forSectors(Collection $sectors, Country $country): Collection
    {
        if ($sectors->isEmpty()) {
            return new Collection;
        }

        $bySection = ActivityContent::query()
            ->whereIn('sector_id', $sectors->pluck('id'))
            ->where('status', ContentStatus::Published)
            ->where(fn ($query) => $query->whereNull('country_id')->orWhere('country_id', $country->id))
            ->get()
            ->groupBy('sector_id')
            ->map(fn (Collection $rows) => $this->dedupe($rows, $country));

        return $sectors
            ->map(fn (Sector $sector) => $bySection->get($sector->id, new Collection))
            ->flatten(1);
    }

    /**
     * @param  Collection<int, ActivityContent>  $rows
     * @return Collection<int, ActivityContent>
     */
    private function dedupe(Collection $rows, Country $country): Collection
    {
        return $rows
            ->groupBy('section')
            ->map(fn (Collection $group) => $group->firstWhere('country_id', $country->id) ?? $group->first())
            ->values();
    }
}
