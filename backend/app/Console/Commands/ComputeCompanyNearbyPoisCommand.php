<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Jobs\ComputeCompanyNearbyPoisJob;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ComputeCompanyNearbyPoisCommand extends Command
{
    private const STALE_AFTER_DAYS = 30;

    protected $signature = 'geo:compute-nearby-pois {--force : Retraiter aussi les entreprises dont le cache est encore récent}';

    protected $description = 'Met en file la régénération du cache de proximité (company_nearby_pois) des entreprises publiées';

    public function handle(): int
    {
        $query = Company::query()
            ->where('content_status', CompanyContentStatus::Published)
            ->where('is_indexable', true)
            ->whereNotNull('location');

        if (! $this->option('force')) {
            $query->where(function (Builder $query): void {
                $query->whereDoesntHave('nearbyPois')
                    ->orWhereHas('nearbyPois', function (Builder $query): void {
                        $query->where('generated_at', '<', now()->subDays(self::STALE_AFTER_DAYS));
                    });
            });
        }

        $count = 0;

        $query->cursor()->each(function (Company $company) use (&$count): void {
            ComputeCompanyNearbyPoisJob::dispatch($company);
            $count++;
        });

        $this->info("{$count} entreprise(s) mise(s) en file pour recalcul de proximité.");

        return self::SUCCESS;
    }
}
