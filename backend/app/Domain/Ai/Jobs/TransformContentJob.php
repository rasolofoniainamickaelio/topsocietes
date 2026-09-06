<?php

declare(strict_types=1);

namespace App\Domain\Ai\Jobs;

use App\Domain\Ai\Actions\TransformContentAction;
use App\Domain\Ai\Enums\GenerationMode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TransformContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Model $content,
        public readonly GenerationMode $mode,
    ) {
        $this->onQueue('ai-generation');
    }

    public function handle(TransformContentAction $action): void
    {
        $action->execute($this->content, $this->mode);
    }
}
