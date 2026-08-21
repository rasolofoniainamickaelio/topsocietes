<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Les 6 pays cibles. `identifier_config` porte des motifs regex de
 * référence (SIREN/SIRET, BCE, MF, ICE, NIF, NEQ) à faire valider par un
 * expert-comptable local avant mise en production — voir docs/DATABASE.md.
 */
class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'code' => 'FR',
                'iso_alpha2' => 'FR',
                'name' => 'France',
                'subdomain' => 'fr',
                'default_locale' => 'fr_FR',
                'currency' => 'EUR',
                'timezone' => 'Europe/Paris',
                'is_active' => true,
                'admin_level_labels' => ['region' => 'Région', 'department' => 'Département', 'city' => 'Commune'],
                'identifier_config' => [
                    'primary' => ['name' => 'SIREN', 'pattern' => '^\d{9}$'],
                    'establishment' => ['name' => 'SIRET', 'pattern' => '^\d{14}$'],
                ],
                'activity_nomenclature_code' => 'NAF',
                'url_patterns' => ['company' => '/{city}/{slug}-{public_id}'],
                'source_config' => ['open_data' => ['insee_sirene', 'api_geo']],
                'settings' => [],
            ],
            [
                'code' => 'BE',
                'iso_alpha2' => 'BE',
                'name' => 'Belgique',
                'subdomain' => 'be',
                'default_locale' => 'fr_BE',
                'currency' => 'EUR',
                'timezone' => 'Europe/Brussels',
                'is_active' => true,
                'admin_level_labels' => ['region' => 'Région', 'province' => 'Province', 'city' => 'Commune'],
                'identifier_config' => [
                    'primary' => ['name' => 'BCE', 'pattern' => '^\d{10}$'],
                ],
                'activity_nomenclature_code' => 'NACEBEL',
                'url_patterns' => ['company' => '/{city}/{slug}-{public_id}'],
                'source_config' => ['open_data' => ['bce_kbo']],
                'settings' => [],
            ],
            [
                'code' => 'TN',
                'iso_alpha2' => 'TN',
                'name' => 'Tunisie',
                'subdomain' => 'tn',
                'default_locale' => 'fr_TN',
                'currency' => 'TND',
                'timezone' => 'Africa/Tunis',
                'is_active' => true,
                'admin_level_labels' => ['governorate' => 'Gouvernorat', 'delegation' => 'Délégation', 'city' => 'Ville'],
                'identifier_config' => [
                    'primary' => ['name' => 'MF', 'pattern' => '^\d{7}[A-Z]$'],
                ],
                'activity_nomenclature_code' => 'NAT',
                'url_patterns' => ['company' => '/{city}/{slug}-{public_id}'],
                'source_config' => ['open_data' => []],
                'settings' => [],
            ],
            [
                'code' => 'MA',
                'iso_alpha2' => 'MA',
                'name' => 'Maroc',
                'subdomain' => 'ma',
                'default_locale' => 'fr_MA',
                'currency' => 'MAD',
                'timezone' => 'Africa/Casablanca',
                'is_active' => true,
                'admin_level_labels' => ['region' => 'Région', 'prefecture' => 'Préfecture', 'city' => 'Ville'],
                'identifier_config' => [
                    'primary' => ['name' => 'ICE', 'pattern' => '^\d{15}$'],
                ],
                'activity_nomenclature_code' => 'NAM',
                'url_patterns' => ['company' => '/{city}/{slug}-{public_id}'],
                'source_config' => ['open_data' => []],
                'settings' => [],
            ],
            [
                'code' => 'DZ',
                'iso_alpha2' => 'DZ',
                'name' => 'Algérie',
                'subdomain' => 'dz',
                'default_locale' => 'fr_DZ',
                'currency' => 'DZD',
                'timezone' => 'Africa/Algiers',
                'is_active' => true,
                'admin_level_labels' => ['wilaya' => 'Wilaya', 'commune' => 'Commune'],
                'identifier_config' => [
                    'primary' => ['name' => 'NIF', 'pattern' => '^\d{15,20}$'],
                ],
                'activity_nomenclature_code' => 'NAA',
                'url_patterns' => ['company' => '/{city}/{slug}-{public_id}'],
                'source_config' => ['open_data' => []],
                'settings' => [],
            ],
            [
                'code' => 'QC',
                'iso_alpha2' => 'CA',
                'name' => 'Québec',
                'subdomain' => 'qc',
                'default_locale' => 'fr_CA',
                'currency' => 'CAD',
                'timezone' => 'America/Montreal',
                'is_active' => true,
                'admin_level_labels' => ['region' => 'Région administrative', 'mrc' => 'MRC', 'city' => 'Municipalité'],
                'identifier_config' => [
                    'primary' => ['name' => 'NEQ', 'pattern' => '^\d{10}$'],
                ],
                'activity_nomenclature_code' => 'SCIAN',
                'url_patterns' => ['company' => '/{city}/{slug}-{public_id}'],
                'source_config' => ['open_data' => ['req_quebec']],
                'settings' => [],
            ],
        ];

        foreach ($countries as $country) {
            Country::query()->updateOrCreate(['code' => $country['code']], $country);
        }
    }
}
