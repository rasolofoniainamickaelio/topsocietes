<?php

declare(strict_types=1);

namespace App\Domain\Seo\Jobs;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Actions\GenerateSitemapsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Jamais à la volée sur une requête (Phase 17) : la génération, même
 * rapide au volume actuel, part toujours en file.
 */
class GenerateSitemapsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Country $country)
    {
        $this->onQueue('geo');
    }

    public function handle(GenerateSitemapsAction $action): void
    {
        $action->execute($this->country);
    }
}
