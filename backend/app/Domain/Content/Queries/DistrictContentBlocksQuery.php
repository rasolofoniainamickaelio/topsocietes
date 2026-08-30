<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ContentSection;
use App\Domain\Content\Models\DistrictContent;
use App\Domain\Geo\Models\District;
use Illuminate\Support\Collection;

/**
 * Contenu direct du quartier — tout contenu publié, sans filtre par
 * activation, même principe que `CityContentBlocksQuery` — complété par le
 * repli de sa ville (`CityContentBlocksQuery::resolve()`, qui inclut déjà
 * son propre repli vers la division administrative) : chaîne complète
 * quartier → commune → département → région en un seul appel (Phase 08).
 *
 * Passe par `resolve()` et non `execute()` sur la requête ville : les
 * clés manquantes ici sont des sections de scope `district`, et
 * `content_sections.key` est unique tous scopes confondus — la ville ne
 * peut pas les avoir enregistrées sous son propre scope `city`.
 */
class DistrictContentBlocksQuery
{
    public function __construct(private readonly CityContentBlocksQuery $cityContentBlocksQuery) {}

    /** @return Collection<int, mixed> */
    public function execute(District $district): Collection
    {
        $direct = DistrictContent::query()
            ->where('district_id', $district->id)
            ->where('status', ContentStatus::Published)
            ->get();

        $enabledSections = ContentSection::query()
            ->where('scope', ContentSectionScope::District)
            ->where('is_enabled', true)
            ->pluck('key')
            ->all();

        $missing = array_values(array_diff($enabledSections, $direct->pluck('section')->all()));

        $cityFallback = $missing === []
            ? collect()
            : $this->cityContentBlocksQuery->resolve($district->city, $missing);

        return collect($direct->all())->concat($cityFallback);
    }
}
