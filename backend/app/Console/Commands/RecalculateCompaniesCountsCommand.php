<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Jobs\RecalculateTerritoryCompaniesCountJob;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\District;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Console\Command;

/**
 * Recalcule `companies_count` pour tous les territoires/activités/secteurs
 * (Phase 13) — jamais un `count()` en direct à l'affichage. Planifiée
 * (`routes/console.php`), et rejouable à tout moment sans effet de bord
 * (idempotent, un simple recalcul complet).
 */
class RecalculateCompaniesCountsCommand extends Command
{
    protected $signature = 'geo:recalculate-companies-counts';

    protected $description = 'Met en file le recalcul de companies_count pour chaque ville, quartier, division, activité et secteur (Phase 13)';

    public function handle(): int
    {
        $total = 0;

        City::query()->select('id')->cursor()->each(function (City $city) use (&$total): void {
            RecalculateTerritoryCompaniesCountJob::dispatch(PageType::City, $city->id);
            $total++;
        });

        District::query()->select('id')->cursor()->each(function (District $district) use (&$total): void {
            RecalculateTerritoryCompaniesCountJob::dispatch(PageType::District, $district->id);
            $total++;
        });

        AdminDivision::query()->select('id')->cursor()->each(function (AdminDivision $division) use (&$total): void {
            RecalculateTerritoryCompaniesCountJob::dispatch(PageType::AdminDivision, $division->id);
            $total++;
        });

        Activity::query()->select('id')->cursor()->each(function (Activity $activity) use (&$total): void {
            RecalculateTerritoryCompaniesCountJob::dispatch(PageType::Activity, $activity->id);
            $total++;
        });

        Sector::query()->select('id')->cursor()->each(function (Sector $sector) use (&$total): void {
            RecalculateTerritoryCompaniesCountJob::dispatch(PageType::Sector, $sector->id);
            $total++;
        });

        $this->info("{$total} recalcul(s) de compteur mis en file.");

        return self::SUCCESS;
    }
}
