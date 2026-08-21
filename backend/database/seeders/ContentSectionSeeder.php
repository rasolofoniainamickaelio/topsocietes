<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentSection;
use App\Enums\ContentSectionScope;
use App\Models\ContentSection as ContentSectionModel;
use Illuminate\Database\Seeder;

/**
 * Amorce la table de référence `content_sections` à partir des clés
 * connues de `App\Enums\ContentSection`. `faq` est partagée par
 * `activity_contents` et `city_activity_contents` (voir docblock de
 * l'enum) : comme `content_sections.key` est unique globalement, elle
 * n'a qu'une seule ligne de configuration ici (scope = Activity, le plus
 * large des deux) — le rattachement réel se fait par la valeur de
 * `section` sur chaque table de contenu, pas par ce `scope` informatif.
 */
class ContentSectionSeeder extends Seeder
{
    /** @var array<string, array{scope: ContentSectionScope, min: int, order: int}> */
    private array $sections = [
        ContentSection::History->value => ['scope' => ContentSectionScope::City, 'min' => 2, 'order' => 10],
        ContentSection::Nature->value => ['scope' => ContentSectionScope::City, 'min' => 1, 'order' => 20],
        ContentSection::Leisure->value => ['scope' => ContentSectionScope::City, 'min' => 1, 'order' => 30],
        ContentSection::Specialty->value => ['scope' => ContentSectionScope::City, 'min' => 1, 'order' => 40],
        ContentSection::Stats->value => ['scope' => ContentSectionScope::City, 'min' => 1, 'order' => 50],

        ContentSection::UnderstandingSector->value => ['scope' => ContentSectionScope::Activity, 'min' => 2, 'order' => 10],
        ContentSection::HowItWorks->value => ['scope' => ContentSectionScope::Activity, 'min' => 2, 'order' => 20],
        ContentSection::Jobs->value => ['scope' => ContentSectionScope::Activity, 'min' => 1, 'order' => 30],
        ContentSection::Diplomas->value => ['scope' => ContentSectionScope::Activity, 'min' => 1, 'order' => 40],
        ContentSection::Regulation->value => ['scope' => ContentSectionScope::Activity, 'min' => 1, 'order' => 50],
        ContentSection::CommonMistakes->value => ['scope' => ContentSectionScope::Activity, 'min' => 1, 'order' => 60],
        ContentSection::HowToChoose->value => ['scope' => ContentSectionScope::Activity, 'min' => 1, 'order' => 70],
        ContentSection::BusinessCreation->value => ['scope' => ContentSectionScope::Activity, 'min' => 1, 'order' => 80],

        ContentSection::LocalOverview->value => ['scope' => ContentSectionScope::CityActivity, 'min' => 2, 'order' => 10],
        ContentSection::LocalHistory->value => ['scope' => ContentSectionScope::CityActivity, 'min' => 1, 'order' => 20],
        ContentSection::LocalSpecifics->value => ['scope' => ContentSectionScope::CityActivity, 'min' => 1, 'order' => 30],
        ContentSection::LocalEconomy->value => ['scope' => ContentSectionScope::CityActivity, 'min' => 1, 'order' => 40],

        ContentSection::Faq->value => ['scope' => ContentSectionScope::Activity, 'min' => 3, 'order' => 90],
    ];

    public function run(): void
    {
        foreach ($this->sections as $key => $config) {
            ContentSectionModel::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'scope' => $config['scope']->value,
                    'is_enabled' => true,
                    'min_facts_required' => $config['min'],
                    'display_order' => $config['order'],
                ]
            );
        }
    }
}
