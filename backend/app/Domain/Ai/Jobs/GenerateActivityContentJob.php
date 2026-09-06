<?php

declare(strict_types=1);

namespace App\Domain\Ai\Jobs;

use App\Domain\Ai\Actions\GenerateActivityContentAction;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateActivityContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Activity|Sector $subject,
        public readonly ?Country $country,
        public readonly ContentSection $section,
    ) {
        $this->onQueue('ai-generation');
    }

    public function handle(GenerateActivityContentAction $action): void
    {
        $action->execute($this->subject, $this->country, $this->section);
    }
}
