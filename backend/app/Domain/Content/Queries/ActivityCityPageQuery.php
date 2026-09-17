<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\BuildActivityCityPathAction;
use App\Domain\Seo\Models\PageRoute;
use App\Domain\Taxonomy\Models\Activity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Assemblage de la page activité×ville (Phase 12) mis en cache (Phase 20) :
 * plusieurs requêtes (croisé, métier, tourisme/culture, voisins, route)
 * pour un contenu qui change rarement à la lecture. Même patron que
 * `CompanyPageBlocksQuery` — TTL court plutôt qu'un graphe d'observers.
 *
 * @phpstan-type CachedPayload array{
 *     path: string,
 *     isIndexable: bool,
 *     blocks: Collection<int, mixed>,
 *     neighborCities: array<int, array{slug: string, name: string, path: string}>
 * }
 */
class ActivityCityPageQuery
{
    private const CACHE_TTL_SECONDS = 900;

    public function __construct(
        private readonly CityActivityContentBlocksQuery $cityActivityBlocks,
        private readonly ActivityContentBlocksQuery $activityBlocks,
        private readonly CityContentBlocksQuery $cityBlocks,
        private readonly BuildActivityCityPathAction $buildPath,
    ) {}

    /**
     * @return CachedPayload
     */
    public function execute(City $city, Activity $activity, Country $country): array
    {
        return Cache::remember(
            self::cacheKey($country, $city, $activity),
            self::CACHE_TTL_SECONDS,
            fn () => $this->build($city, $activity, $country),
        );
    }

    public static function cacheKey(Country $country, City $city, Activity $activity): string
    {
        return "activity-city-page:{$country->id}:{$city->id}:{$activity->id}";
    }

    /**
     * @return CachedPayload
     */
    private function build(City $city, Activity $activity, Country $country): array
    {
        $path = $this->buildPath->execute($city, $activity, $country);

        $neighborCities = $city->neighborLinks()
            ->with('neighborCity')
            ->get()
            ->map(fn ($link) => $link->neighborCity)
            ->filter()
            ->map(fn (City $neighbor) => [
                'slug' => $neighbor->slug,
                'name' => $neighbor->name,
                'path' => $this->buildPath->execute($neighbor, $activity, $country),
            ])
            ->values()
            ->all();

        $route = PageRoute::query()
            ->where('country_id', $country->id)
            ->where('path', $path)
            ->first();

        $blocks = $this->cityActivityBlocks->execute($city, $activity)
            ->concat($this->activityBlocks->forActivity($activity, $country))
            ->concat($this->cityBlocks->resolve($city, ['history', 'leisure']));

        return [
            'path' => $path,
            'isIndexable' => $route === null || $route->is_indexable,
            'blocks' => $blocks,
            'neighborCities' => $neighborCities,
        ];
    }
}
