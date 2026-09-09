<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Support\Collection;

/**
 * Contenu croisé ville×activité (Phase 08/12) : pas de repli générique/pays
 * ici (contrairement à `ActivityContentBlocksQuery`) — `city_activity_contents`
 * n'a pas de colonne `country_id`, et la contrainte unique
 * `(city_id, activity_id, locale, section)` garantit déjà au plus une ligne
 * par section et par langue.
 */
class CityActivityContentBlocksQuery
{
    /** @return Collection<int, CityActivityContent> */
    public function execute(City $city, Activity $activity): Collection
    {
        return CityActivityContent::query()
            ->where('city_id', $city->id)
            ->where('activity_id', $activity->id)
            ->where('status', ContentStatus::Published)
            ->get();
    }
}
