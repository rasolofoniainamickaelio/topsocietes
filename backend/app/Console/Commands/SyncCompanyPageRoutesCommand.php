<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Company\Models\Company;
use App\Domain\Seo\Jobs\SyncCompanyPageRouteJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill (Phase 16) : toute entreprise créée avant l'existence de
 * `CompanyObserver::created()`, ou dont le job initial s'est perdu (queue
 * jamais drainée, Redis vidé, seed en insertion brute qui contourne les
 * events Eloquent) n'a jamais eu sa route synchronisée. Sans elle, sa
 * fiche est invisible du frontend (`/resolve` échoue, le catch-all rend
 * un 404) alors qu'elle existe bel et bien en base — découvert en testant
 * la Phase 16 sur les données de dev.
 */
class SyncCompanyPageRoutesCommand extends Command
{
    protected $signature = 'seo:sync-company-routes';

    protected $description = "Met en file la synchronisation de route pour chaque entreprise qui n'en a pas encore une (Phase 16/18)";

    public function handle(): int
    {
        $count = 0;

        Company::query()
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.entity_id', 'companies.id')
                    ->where('routes.entity_type', (new Company)->getMorphClass());
            })
            ->cursor()
            ->each(function (Company $company) use (&$count): void {
                SyncCompanyPageRouteJob::dispatch($company);
                $count++;
            });

        $this->info("{$count} entreprise(s) mise(s) en file pour synchronisation de route.");

        return self::SUCCESS;
    }
}
