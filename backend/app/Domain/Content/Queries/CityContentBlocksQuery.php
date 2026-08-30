<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\ContentSection;
use App\Domain\Geo\Models\City;
use Illuminate\Support\Collection;

/**
 * Contenu direct de la ville — tout contenu publié, sans filtre par
 * activation (`content_sections` ne restreint jamais ce qui est déjà
 * publié, seulement ce qui déclenche une recherche de repli) — complété
 * par un repli vers sa division administrative (département → région,
 * Phase 08) pour chaque section activée qui n'a pas de ligne publiée au
 * niveau ville.
 */
class CityContentBlocksQuery
{
    public function __construct(private readonly ResolveAdminDivisionContentQuery $adminDivisionQuery) {}

    /** @return Collection<int, CityContent|AdminDivisionContent> */
    public function execute(City $city): Collection
    {
        $direct = CityContent::query()
            ->where('city_id', $city->id)
            ->where('status', ContentStatus::Published)
            ->get();

        $enabledSections = ContentSection::query()
            ->where('scope', ContentSectionScope::City)
            ->where('is_enabled', true)
            ->pluck('key')
            ->all();

        $missing = array_values(array_diff($enabledSections, $direct->pluck('section')->all()));

        $fallback = $missing === []
            ? collect()
            : $this->adminDivisionQuery->execute($city->adminDivision, $missing);

        return collect($direct->all())->concat($fallback);
    }

    /**
     * Cherche le contenu de la ville pour un ensemble de clés précises
     * (empruntées par un territoire enfant, ex. un quartier pour ses
     * propres sections manquantes), puis complète par le repli région pour
     * celles toujours introuvables. `content_sections.key` est unique tous
     * scopes confondus : ces clés n'appartiennent pas au scope ville, d'où
     * une recherche directe plutôt qu'un recalcul via `execute()`.
     *
     * @param  array<int, string>  $sections
     * @return Collection<int, CityContent|AdminDivisionContent>
     */
    public function resolve(City $city, array $sections): Collection
    {
        $direct = CityContent::query()
            ->where('city_id', $city->id)
            ->where('status', ContentStatus::Published)
            ->whereIn('section', $sections)
            ->get();

        $missing = array_values(array_diff($sections, $direct->pluck('section')->all()));

        $fallback = $missing === []
            ? collect()
            : $this->adminDivisionQuery->execute($city->adminDivision, $missing);

        return collect($direct->all())->concat($fallback);
    }
}
