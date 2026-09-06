<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\DistrictContent;
use App\Domain\Content\Models\Fact;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\District;
use App\Domain\Seo\Enums\NoindexReason;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Enums\PublicationDecision;
use App\Domain\Seo\Models\PagePublicationDecision;
use App\Domain\Seo\Models\PagePublicationRule;
use App\Domain\Seo\Models\PageRoute;

/**
 * Le système décide qu'une page ne mérite pas d'être indexée (Phase 18) :
 * seuils configurables par type de page et par pays (`PagePublicationRule`),
 * jamais codés en dur. Toujours en tâche de fond (après import, après
 * génération IA), jamais synchrone sur une requête.
 *
 * Seuls Company/City/District/AdminDivision savent aujourd'hui calculer
 * leurs statistiques (Activity, ActivityCity : pas encore de source de
 * comptage propre tant que leur `PageRoute` n'est pas peuplée, Phase 16) —
 * une cible non reconnue est publiable par défaut, jamais bloquée faute de
 * pouvoir être évaluée.
 */
class EvaluatePagePublicationAction
{
    public function execute(PageRoute $route): PagePublicationDecision
    {
        $unpublishedReason = $this->unpublishedReason($route);

        if ($unpublishedReason !== null) {
            return $this->recordDecision($route, PublicationDecision::Noindex, $unpublishedReason, 0, ['entity not published']);
        }

        $stats = $this->countStats($route);
        $rule = $this->findRule($route);

        if ($stats === null || $rule === null) {
            return $this->recordDecision($route, PublicationDecision::Publish, null, 100, []);
        }

        [$reasons, $checks, $passed] = $this->evaluateThresholds($stats, $rule);

        $score = $checks === 0 ? 100 : (int) round($passed / $checks * 100);
        $decision = $reasons === [] ? PublicationDecision::Publish : PublicationDecision::Noindex;
        $noindexReason = $reasons === [] ? null : NoindexReason::BelowPublicationThreshold;

        return $this->recordDecision($route, $decision, $noindexReason, $score, $reasons);
    }

    /**
     * Un flag de publication au niveau du sujet lui-même prime toujours sur
     * les seuils de contenu : une entreprise non publiée n'a pas besoin
     * d'être "pauvre" pour être exclue.
     */
    private function unpublishedReason(PageRoute $route): ?NoindexReason
    {
        if ($route->page_type !== PageType::Company) {
            return null;
        }

        $company = Company::find($route->entity_id);

        if ($company === null) {
            return NoindexReason::Unpublished;
        }

        return ($company->content_status === CompanyContentStatus::Published && $company->is_indexable)
            ? null
            : NoindexReason::Unpublished;
    }

    /**
     * @return array{companies: int, facts: int, content_sections: int, word_count: int}|null
     */
    private function countStats(PageRoute $route): ?array
    {
        return match ($route->page_type) {
            PageType::Company => $this->companyStats($route->entity_id),
            PageType::City => $this->cityStats($route->entity_id),
            PageType::District => $this->districtStats($route->entity_id),
            PageType::AdminDivision => $this->adminDivisionStats($route->entity_id),
            default => null,
        };
    }

    /**
     * @return array{companies: int, facts: int, content_sections: int, word_count: int}|null
     */
    private function companyStats(int $entityId): ?array
    {
        $company = Company::find($entityId);

        if ($company === null) {
            return null;
        }

        return [
            'companies' => 1,
            'facts' => 0,
            'content_sections' => 0,
            'word_count' => str_word_count(strip_tags((string) $company->about_text)),
        ];
    }

    /**
     * @return array{companies: int, facts: int, content_sections: int, word_count: int}|null
     */
    private function cityStats(int $entityId): ?array
    {
        $city = City::find($entityId);

        if ($city === null) {
            return null;
        }

        $contents = CityContent::query()->where('city_id', $entityId)->where('status', ContentStatus::Published)->get();

        return [
            'companies' => $city->companies_count ?? 0,
            'facts' => Fact::query()->where('subject_type', $city->getMorphClass())->where('subject_id', $entityId)->usable()->count(),
            'content_sections' => $contents->count(),
            'word_count' => $contents->sum(fn (CityContent $content) => str_word_count(strip_tags((string) $content->body))),
        ];
    }

    /**
     * @return array{companies: int, facts: int, content_sections: int, word_count: int}|null
     */
    private function districtStats(int $entityId): ?array
    {
        $district = District::find($entityId);

        if ($district === null) {
            return null;
        }

        $contents = DistrictContent::query()->where('district_id', $entityId)->where('status', ContentStatus::Published)->get();

        return [
            'companies' => $district->companies_count ?? 0,
            'facts' => Fact::query()->where('subject_type', $district->getMorphClass())->where('subject_id', $entityId)->usable()->count(),
            'content_sections' => $contents->count(),
            'word_count' => $contents->sum(fn (DistrictContent $content) => str_word_count(strip_tags((string) $content->body))),
        ];
    }

    /**
     * @return array{companies: int, facts: int, content_sections: int, word_count: int}|null
     */
    private function adminDivisionStats(int $entityId): ?array
    {
        $division = AdminDivision::find($entityId);

        if ($division === null) {
            return null;
        }

        $contents = AdminDivisionContent::query()
            ->where('admin_division_id', $entityId)
            ->where('status', ContentStatus::Published)
            ->get();

        return [
            'companies' => Company::query()->where('admin_division_id', $entityId)->count(),
            'facts' => 0,
            'content_sections' => $contents->count(),
            'word_count' => $contents->sum(fn (AdminDivisionContent $content) => str_word_count(strip_tags((string) $content->body))),
        ];
    }

    private function findRule(PageRoute $route): ?PagePublicationRule
    {
        return PagePublicationRule::query()
            ->where('page_type', $route->page_type)
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('country_id', $route->country_id)->orWhereNull('country_id'))
            ->orderByRaw('country_id IS NULL')
            ->first();
    }

    /**
     * @param  array{companies: int, facts: int, content_sections: int, word_count: int}  $stats
     * @return array{0: array<int, string>, 1: int, 2: int}
     */
    private function evaluateThresholds(array $stats, PagePublicationRule $rule): array
    {
        $reasons = [];
        $checks = 0;
        $passed = 0;

        foreach ([
            'min_companies' => ['companies', $rule->min_companies],
            'min_facts' => ['facts', $rule->min_facts],
            'min_content_sections' => ['content_sections', $rule->min_content_sections],
            'min_word_count' => ['word_count', $rule->min_word_count],
        ] as $ruleKey => [$statKey, $threshold]) {
            // Un seuil à 0 (valeur par défaut de la colonne, jamais NULL en
            // base) équivaut à "non contraint" : un compte ne peut jamais
            // être négatif, `actual >= 0` est toujours vrai.
            if ($threshold <= 0) {
                continue;
            }

            $checks++;
            $actual = $stats[$statKey];

            if ($actual >= $threshold) {
                $passed++;
            } else {
                $reasons[] = "{$ruleKey}: {$actual} < {$threshold}";
            }
        }

        return [$reasons, $checks, $passed];
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function recordDecision(PageRoute $route, PublicationDecision $decision, ?NoindexReason $noindexReason, int $score, array $reasons): PagePublicationDecision
    {
        $route->update([
            'is_indexable' => $decision === PublicationDecision::Publish,
            'noindex_reason' => $noindexReason,
        ]);

        return PagePublicationDecision::create([
            'route_id' => $route->id,
            'score' => $score,
            'decision' => $decision,
            'reasons' => $reasons,
            'evaluated_at' => now(),
        ]);
    }
}
