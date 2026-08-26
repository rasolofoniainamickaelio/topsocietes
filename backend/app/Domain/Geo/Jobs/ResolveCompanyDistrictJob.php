<?php

declare(strict_types=1);

namespace App\Domain\Geo\Jobs;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Actions\ResolveCompanyDistrictAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveCompanyDistrictJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Company $company) {}

    public function handle(ResolveCompanyDistrictAction $action): void
    {
        $action->execute($this->company);
    }
}
