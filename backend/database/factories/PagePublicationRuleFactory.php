<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PagePublicationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagePublicationRule>
 */
class PagePublicationRuleFactory extends Factory
{
    protected $model = PagePublicationRule::class;

    public function definition(): array
    {
        return [
            'page_type' => PageType::ActivityCity,
            'country_id' => null,
            'min_companies' => 3,
            'min_facts' => 1,
            'min_content_sections' => 1,
            'min_word_count' => 150,
            'is_active' => true,
        ];
    }
}
