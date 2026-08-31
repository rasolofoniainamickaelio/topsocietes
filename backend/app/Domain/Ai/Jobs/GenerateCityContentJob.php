<?php

declare(strict_types=1);

namespace App\Domain\Ai\Jobs;

use App\Domain\Ai\Actions\GenerateCityContentAction;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Première utilisation réelle de la file `ai-generation` (Horizon,
 * `tries: 3`, déjà provisionnée mais jamais consommée avant cette phase).
 */
class GenerateCityContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly City $city,
        public readonly ContentSection $section,
    ) {
        $this->onQueue('ai-generation');
    }

    public function handle(GenerateCityContentAction $action): void
    {
        $action->execute($this->city, $this->section);
    }
}
