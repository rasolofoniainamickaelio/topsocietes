<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Enums\SourceProvider;
use App\Domain\Content\Models\Source;
use Illuminate\Database\Seeder;

/**
 * Amorce les 3 sources publiques de la Phase 09. `country_id` reste
 * `null` : ces trois fournisseurs sont mondiaux, pas propres à un pays.
 * `reliability_score` fixe la fiabilité par défaut de chaque source —
 * ajustable en back-office, valeurs de départ non validées.
 */
class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            SourceProvider::Wikipedia->value => ['name' => 'Wikipedia', 'base_url' => 'https://fr.wikipedia.org', 'reliability' => 70],
            SourceProvider::Wikidata->value => ['name' => 'Wikidata', 'base_url' => 'https://www.wikidata.org', 'reliability' => 80],
            SourceProvider::OpenStreetMap->value => ['name' => 'OpenStreetMap (Nominatim)', 'base_url' => 'https://nominatim.openstreetmap.org', 'reliability' => 60],
        ];

        foreach ($sources as $provider => $config) {
            Source::query()->updateOrCreate(
                ['provider' => $provider, 'country_id' => null],
                [
                    'name' => $config['name'],
                    'base_url' => $config['base_url'],
                    'license' => null,
                    'reliability_score' => $config['reliability'],
                    'is_active' => true,
                ],
            );
        }
    }
}
