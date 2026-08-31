<?php

declare(strict_types=1);

namespace App\Domain\Content\Jobs;

use App\Domain\Content\Actions\CollectCityFactsAction;
use App\Domain\Geo\Models\City;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CollectCityFactsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly City $city) {}

    public function handle(CollectCityFactsAction $action): void
    {
        $action->execute($this->city);
    }
}
