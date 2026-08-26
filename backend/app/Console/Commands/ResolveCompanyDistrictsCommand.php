<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Jobs\ResolveCompanyDistrictJob;
use Illuminate\Console\Command;

class ResolveCompanyDistrictsCommand extends Command
{
    protected $signature = 'geo:resolve-districts';

    protected $description = "Met en file le rattachement quartier pour chaque entreprise géolocalisée qui n'en a pas encore un";

    public function handle(): int
    {
        $count = 0;

        Company::query()
            ->whereNull('district_id')
            ->whereNotNull('location')
            ->whereNotNull('city_id')
            ->cursor()
            ->each(function (Company $company) use (&$count): void {
                ResolveCompanyDistrictJob::dispatch($company);
                $count++;
            });

        $this->info("{$count} entreprise(s) mise(s) en file pour rattachement quartier.");

        return self::SUCCESS;
    }
}
