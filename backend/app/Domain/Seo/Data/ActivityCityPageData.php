<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Bagage assemblé par `ActivityCityShowController` (Phase 12) — pas une
 * entité Eloquent unique (page composite, `routes.entity_id` reste `null`
 * pour ce type), donc pas de Resource `@mixin` classique sur un modèle.
 */
class ActivityCityPageData extends Data
{
    /**
     * `blocks` mêle du contenu croisé (`CityActivityContent`, sans
     * `title`) et du contenu générique du métier (`ActivityContent`, avec
     * `title`) — `ContentBlockResource` gère les deux indifféremment
     * (Phase 08).
     *
     * @param  Collection<int, CityActivityContent|ActivityContent>  $blocks
     * @param  array<int, array{slug: string, name: string, path: string}>  $neighborCities
     */
    public function __construct(
        public readonly City $city,
        public readonly Activity $activity,
        public readonly string $path,
        public readonly bool $isIndexable,
        public readonly Collection $blocks,
        public readonly array $neighborCities,
    ) {}
}
