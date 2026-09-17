<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Geo\Models\Country;
use App\Domain\Import\Models\ImportMapping;
use Illuminate\Database\Seeder;

/**
 * Mapping par défaut Maroc pour `docs/MAROC_ENTREPRISE.xlsx` (exporté en
 * CSV) — colonnes et transformers déjà câblés dans `RowTransformers`
 * (Phase 03). Sans ce seeder, `import:companies ma` échoue faute de
 * mapping (Phase 19).
 */
class MoroccoImportMappingSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('subdomain', 'ma')->first();

        if ($country === null) {
            return;
        }

        ImportMapping::query()->updateOrCreate(
            ['country_id' => $country->id, 'name' => 'Maroc — MAROC_ENTREPRISE (défaut)'],
            [
                'column_map' => [
                    '__row_national_id__' => 'national_id',
                    "Nom d'entreprise" => 'legal_name',
                    '__row_city_slug__' => 'city_slug',
                    '__row_district_slug__' => 'district_slug',
                    'Catégories' => 'activity_category_raw',
                ],
                'transformers' => [
                    'national_id' => 'morocco_synthetic_id',
                    'legal_name' => 'trim',
                    'city_slug' => 'morocco_city_slug',
                    'district_slug' => 'morocco_district_slug',
                    'activity_category_raw' => 'trim',
                ],
                'is_default' => true,
            ],
        );
    }
}
