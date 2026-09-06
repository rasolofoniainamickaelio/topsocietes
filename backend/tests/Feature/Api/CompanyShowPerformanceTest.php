<?php

declare(strict_types=1);

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Queries\CompanyPageBlocksQuery;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Objective la Phase 20 : une entreprise dont l'activité appartient à
 * plusieurs secteurs déclenchait une requête `activity_contents` PAR
 * secteur avant que `ActivityContentBlocksQuery::forSectors()` ne les
 * regroupe en une seule (Phase 08). Compare le nombre de requêtes pour 1 et
 * pour 8 secteurs : la Phase 20 exige que ce nombre reste stable, jamais
 * proportionnel au nombre de secteurs.
 */
it('keeps the query count constant regardless of how many sectors the activity belongs to', function (): void {
    $country = Country::factory()->create();
    $city = City::factory()->for($country)->create();

    $countQueriesFor = function (int $sectorCount) use ($country, $city): int {
        Cache::flush();

        $activity = Activity::factory()->create();
        $sectors = Sector::factory()->count($sectorCount)->create();
        $activity->sectors()->attach($sectors);

        foreach ($sectors as $sector) {
            ActivityContent::factory()->create([
                'activity_id' => null,
                'sector_id' => $sector->id,
                'country_id' => null,
                'status' => ContentStatus::Published,
            ]);
        }

        $company = Company::factory()
            ->for($country)
            ->for($city, 'city')
            ->for($activity)
            ->create(['content_status' => CompanyContentStatus::Published, 'is_indexable' => true]);

        $company->load(['city', 'district', 'activity.sectors', 'country']);

        DB::enableQueryLog();
        app(CompanyPageBlocksQuery::class)->execute($company);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        return $count;
    };

    $queriesWithOneSector = $countQueriesFor(1);
    $queriesWithEightSectors = $countQueriesFor(8);

    expect($queriesWithEightSectors)->toBe($queriesWithOneSector);
})->group('performance');
