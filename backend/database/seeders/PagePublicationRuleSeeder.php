<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PagePublicationRule;
use Illuminate\Database\Seeder;

/**
 * Règles globales (country_id null) — sans elles, le scoring Phase 18
 * reste inerte en base réelle. Seuils volontairement bas pour ne pas
 * bloquer un premier jeu de pages pendant le test industriel (Phase 19).
 */
class PagePublicationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            PageType::Company->value => ['min_companies' => 0, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
            PageType::City->value => ['min_companies' => 1, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
            PageType::AdminDivision->value => ['min_companies' => 1, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
            PageType::Activity->value => ['min_companies' => 1, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
            PageType::ActivityCity->value => ['min_companies' => 3, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
            PageType::Sector->value => ['min_companies' => 1, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
            PageType::Country->value => ['min_companies' => 0, 'min_facts' => 0, 'min_content_sections' => 0, 'min_word_count' => 0],
        ];

        foreach ($defaults as $pageType => $thresholds) {
            PagePublicationRule::query()->updateOrCreate(
                ['page_type' => $pageType, 'country_id' => null],
                [...$thresholds, 'is_active' => true],
            );
        }
    }
}
