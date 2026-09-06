<?php

declare(strict_types=1);

namespace App\Domain\Content\Queries;

use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Compose, pour une fiche entreprise, tous les blocs territoriaux et
 * sectoriels pertinents (Phase 08) : quartier→commune→région (ou
 * commune→région si pas de quartier résolu), activité et ses secteurs, et
 * le contenu croisé commune×activité — jamais dupliqué en base, assemblé à
 * la lecture depuis les tables mutualisées.
 */
/**
 * Résultats mis en cache (Phase 20) : chaque appel compose plusieurs
 * requêtes (chaîne de repli géographique, secteurs, croisé ville×activité)
 * pour un contenu qui ne change qu'à la publication d'un bloc territorial —
 * bien plus rare qu'une simple lecture de fiche. TTL court plutôt qu'une
 * invalidation fine par observer sur chaque table de contenu (5 modèles
 * distincts) : accepte une fenêtre de latence de publication au lieu
 * d'alourdir considérablement le graphe d'observers pour un gain marginal.
 */
class CompanyPageBlocksQuery
{
    private const CACHE_TTL_SECONDS = 900;

    public function __construct(
        private readonly CityContentBlocksQuery $cityBlocks,
        private readonly DistrictContentBlocksQuery $districtBlocks,
        private readonly ActivityContentBlocksQuery $activityBlocks,
    ) {}

    /** @return Collection<int, mixed> */
    public function execute(Company $company): Collection
    {
        return Cache::remember(
            self::cacheKey($company),
            self::CACHE_TTL_SECONDS,
            fn () => $this->build($company),
        );
    }

    public static function cacheKey(Company $company): string
    {
        return "company-blocks:{$company->id}";
    }

    /** @return Collection<int, mixed> */
    private function build(Company $company): Collection
    {
        $blocks = collect();

        if ($company->district !== null) {
            $blocks = $blocks->concat($this->districtBlocks->execute($company->district));
        } elseif ($company->city !== null) {
            $blocks = $blocks->concat($this->cityBlocks->execute($company->city));
        }

        if ($company->activity === null) {
            return $blocks;
        }

        $blocks = $blocks->concat($this->activityBlocks->forActivity($company->activity, $company->country));
        $blocks = $blocks->concat($this->activityBlocks->forSectors($company->activity->sectors, $company->country));

        if ($company->city !== null) {
            $blocks = $blocks->concat(
                CityActivityContent::query()
                    ->where('city_id', $company->city_id)
                    ->where('activity_id', $company->activity_id)
                    ->where('status', ContentStatus::Published)
                    ->get(),
            );
        }

        return $blocks;
    }
}
