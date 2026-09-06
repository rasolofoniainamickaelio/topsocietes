<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Billing\Actions\ExpireSubscriptionsAction;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire les abonnements dont la période payée est dépassée et remasque leurs contacts';

    public function handle(ExpireSubscriptionsAction $action): int
    {
        $count = $action->execute();

        $this->info("{$count} abonnement(s) expiré(s).");

        return self::SUCCESS;
    }
}
