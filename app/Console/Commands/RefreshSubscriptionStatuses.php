<?php

namespace App\Console\Commands;

use App\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class RefreshSubscriptionStatuses extends Command
{
    protected $signature = 'winner-gym:subscriptions-refresh';

    protected $description = 'Refresh subscription and installment lifecycle statuses';

    public function handle(SubscriptionLifecycleService $lifecycle): int
    {
        $result = $lifecycle->refresh();

        $this->components->info('Subscription lifecycle refreshed.');
        $this->table(['Transition', 'Rows'], collect($result)->map(
            fn (int $count, string $transition): array => [$transition, $count],
        )->values()->all());

        return self::SUCCESS;
    }
}
