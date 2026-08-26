<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycleService
{
    /**
     * @return array<string, int>
     */
    public function refresh(): array
    {
        return DB::transaction(function (): array {
            $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
            $todayDate = $today->toDateString();
            $expiringDate = $today
                ->addDays(max(1, (int) config('winner-gym.subscriptions.expiring_soon_days', 7)))
                ->toDateString();

            $overdueInstallments = SubscriptionInstallment::query()
                ->where('status', 'pending')
                ->whereDate('due_date', '<', $todayDate)
                ->update(['status' => 'overdue']);

            $expired = Subscription::query()
                ->whereNotIn('status', ['expired', 'cancelled', 'refunded'])
                ->whereDate('end_date', '<', $todayDate)
                ->update(['status' => 'expired']);

            $activated = Subscription::query()
                ->where('status', 'upcoming')
                ->whereDate('start_date', '<=', $todayDate)
                ->whereDate('end_date', '>=', $todayDate)
                ->update(['status' => 'active']);

            $financialOverdue = Subscription::query()
                ->whereIn('status', ['active', 'expiring_soon'])
                ->whereHas('installments', fn ($query) => $query->where('status', 'overdue'))
                ->update(['status' => 'financial_overdue']);

            $financiallyCleared = Subscription::query()
                ->where('status', 'financial_overdue')
                ->whereDoesntHave('installments', fn ($query) => $query->where('status', 'overdue'))
                ->whereDate('end_date', '>', $expiringDate)
                ->update(['status' => 'active']);

            $expiringSoon = Subscription::query()
                ->whereIn('status', ['active', 'financial_overdue'])
                ->whereDoesntHave('installments', fn ($query) => $query->where('status', 'overdue'))
                ->whereDate('end_date', '>=', $todayDate)
                ->whereDate('end_date', '<=', $expiringDate)
                ->update(['status' => 'expiring_soon']);

            return compact(
                'overdueInstallments',
                'expired',
                'activated',
                'financialOverdue',
                'financiallyCleared',
                'expiringSoon',
            );
        });
    }
}
