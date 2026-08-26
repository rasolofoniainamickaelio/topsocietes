<?php

declare(strict_types=1);

namespace App\Domain\Geo\Jobs;

use App\Domain\Geo\Actions\ComputeCityNeighborsAction;
use App\Domain\Geo\Models\City;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeCityNeighborsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly City $city) {}

    public function handle(ComputeCityNeighborsAction $action): void
    {
        $action->execute($this->city);
    }
}
