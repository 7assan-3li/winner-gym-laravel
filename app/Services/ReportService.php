<?php

namespace App\Services;

use App\Models\AppointmentPayment;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array{
     *     currency: string,
     *     subscription_gross_revenue: float,
     *     subscription_refunds: float,
     *     subscription_revenue: float,
     *     nutrition_revenue: float,
     *     product_revenue: float,
     *     product_cogs: float,
     *     product_profit: float,
     *     expenses: float,
     *     net: float,
     *     attendance_count: int,
     *     appointments_count: int,
     *     new_members_count: int
     * }
     */
    public function summary(string $from, string $to, string $gender = 'all', string $currency = 'YER'): array
    {
        $currency = strtoupper($currency);

        abort_unless(in_array($currency, ['YER', 'SAR'], true), 422);
        abort_unless(in_array($gender, ['all', 'male', 'female'], true), 422);

        $tz = config('app.timezone', 'Asia/Aden');
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $fromDt = CarbonImmutable::parse($from, $tz)->startOfDay();
        $toExclusiveDt = CarbonImmutable::parse($to, $tz)->addDay()->startOfDay();

        $fromUtc = $isSqlite ? $fromDt->format('Y-m-d H:i:s') : $fromDt->utc();
        $toExclusiveUtc = $isSqlite ? $toExclusiveDt->format('Y-m-d H:i:s') : $toExclusiveDt->utc();

        // The WINNER GYM schema is fixed by our migrations, so we avoid
        // Schema::hasTable / hasColumn checks on every page request.
        // This reduces a report from many remote round-trips to one query.
        $row = DB::selectOne(
            <<<'SQL'
            select
                coalesce((
                    select sum(p.amount)
                    from subscription_payments p
                    join subscriptions s on s.id = p.subscription_id
                    join members m on m.id = s.member_id
                    where p.currency = :sub_currency
                      and p.status = 'completed'
                      and p.paid_at >= :sub_from
                      and p.paid_at < :sub_to
                      and (:sub_gender_all = 'all' or m.gender = :sub_gender)
                ), 0) as subscription_revenue,

                coalesce((
                    select sum(r.amount)
                    from subscription_refunds r
                    join subscriptions s on s.id = r.subscription_id
                    join members m on m.id = s.member_id
                    where r.currency = :refund_currency
                      and r.status = 'completed'
                      and r.processed_at >= :refund_from
                      and r.processed_at < :refund_to
                      and (:refund_gender_all = 'all' or m.gender = :refund_gender)
                ), 0) as subscription_refunds,

                coalesce((
                    select sum(ap.amount)
                    from appointment_payments ap
                    join appointments a on a.id = ap.appointment_id
                    left join members m on m.id = a.member_id
                    left join nutrition_clients nc on nc.id = a.nutrition_client_id
                    where ap.currency = :nutrition_currency
                      and ap.status = 'paid'
                      and ap.paid_at >= :nutrition_from
                      and ap.paid_at < :nutrition_to
                      and (
                          :nutrition_gender_all = 'all'
                          or coalesce(m.gender, nc.gender) = :nutrition_gender
                      )
                ), 0) as nutrition_revenue,

                coalesce((
                    select sum(s.total_amount)
                    from sales s
                    left join members m on m.id = s.member_id
                    where s.currency = :sales_currency
                      and s.status = 'completed'
                      and s.sold_at >= :sales_from
                      and s.sold_at < :sales_to
                      and (:sales_gender_all = 'all' or m.gender = :sales_gender)
                ), 0) as product_revenue,

                coalesce((
                    select sum(i.quantity * i.unit_cost)
                    from sale_items i
                    join sales s on s.id = i.sale_id
                    left join members m on m.id = s.member_id
                    where s.currency = :cogs_currency
                      and s.status = 'completed'
                      and s.sold_at >= :cogs_from
                      and s.sold_at < :cogs_to
                      and (:cogs_gender_all = 'all' or m.gender = :cogs_gender)
                ), 0) as product_cogs,

                coalesce((
                    select sum(e.amount)
                    from expenses e
                    where e.currency = :expense_currency
                      and e.status = 'approved'
                      and e.expense_date between :expense_from and :expense_to
                ), 0) as expenses,

                (
                    select count(*)
                    from attendances a
                    join members m on m.id = a.member_id
                    where a.attendance_date between :attendance_from and :attendance_to
                      and (:attendance_gender_all = 'all' or m.gender = :attendance_gender)
                ) as attendance_count,

                (
                    select count(*)
                    from appointments a
                    left join members m on m.id = a.member_id
                    left join nutrition_clients nc on nc.id = a.nutrition_client_id
                    where a.appointment_date between :appointments_from and :appointments_to
                      and a.status <> 'cancelled'
                      and (
                          :appointments_gender_all = 'all'
                          or coalesce(m.gender, nc.gender) = :appointments_gender
                      )
                ) as appointments_count,

                (
                    select count(*)
                    from members m
                    where m.registration_date between :members_from and :members_to
                      and (:members_gender_all = 'all' or m.gender = :members_gender)
                ) as new_members_count
            SQL,
            [
                'sub_currency' => $currency,
                'sub_from' => $fromUtc,
                'sub_to' => $toExclusiveUtc,
                'sub_gender_all' => $gender,
                'sub_gender' => $gender,

                'refund_currency' => $currency,
                'refund_from' => $fromUtc,
                'refund_to' => $toExclusiveUtc,
                'refund_gender_all' => $gender,
                'refund_gender' => $gender,

                'nutrition_currency' => $currency,
                'nutrition_from' => $fromUtc,
                'nutrition_to' => $toExclusiveUtc,
                'nutrition_gender_all' => $gender,
                'nutrition_gender' => $gender,

                'sales_currency' => $currency,
                'sales_from' => $fromUtc,
                'sales_to' => $toExclusiveUtc,
                'sales_gender_all' => $gender,
                'sales_gender' => $gender,

                'cogs_currency' => $currency,
                'cogs_from' => $fromUtc,
                'cogs_to' => $toExclusiveUtc,
                'cogs_gender_all' => $gender,
                'cogs_gender' => $gender,

                'expense_currency' => $currency,
                'expense_from' => $from,
                'expense_to' => $to,

                'attendance_from' => $from,
                'attendance_to' => $to,
                'attendance_gender_all' => $gender,
                'attendance_gender' => $gender,

                'appointments_from' => $from,
                'appointments_to' => $to,
                'appointments_gender_all' => $gender,
                'appointments_gender' => $gender,

                'members_from' => $from,
                'members_to' => $to,
                'members_gender_all' => $gender,
                'members_gender' => $gender,
            ]
        );

        $subscriptionGrossRevenue = (float) $row->subscription_revenue;
        $subscriptionRefunds = (float) $row->subscription_refunds;
        $subscriptionRevenue = round($subscriptionGrossRevenue - $subscriptionRefunds, 2);
        $nutritionRevenue = (float) $row->nutrition_revenue;
        $productRevenue = (float) $row->product_revenue;
        $productCogs = (float) $row->product_cogs;
        $expenses = (float) $row->expenses;
        $productProfit = round($productRevenue - $productCogs, 2);

        return [
            'currency' => $currency,
            'subscription_gross_revenue' => round($subscriptionGrossRevenue, 2),
            'subscription_refunds' => round($subscriptionRefunds, 2),
            'subscription_revenue' => $subscriptionRevenue,
            'nutrition_revenue' => round($nutritionRevenue, 2),
            'product_revenue' => round($productRevenue, 2),
            'product_cogs' => round($productCogs, 2),
            'product_profit' => $productProfit,
            'expenses' => round($expenses, 2),
            'net' => round($subscriptionRevenue + $nutritionRevenue + $productProfit - $expenses, 2),
            'attendance_count' => (int) $row->attendance_count,
            'appointments_count' => (int) $row->appointments_count,
            'new_members_count' => (int) $row->new_members_count,
        ];
    }

    /**
     * Cashier Shift Reconciliation & Cash Handover Summary
     *
     * @return array{
     *     currency: string,
     *     date: string,
     *     cashier_id: int|null,
     *     cashier_name: string,
     *     subscription_cash: float,
     *     subscription_transfer: float,
     *     nutrition_cash: float,
     *     nutrition_transfer: float,
     *     sales_cash: float,
     *     sales_transfer: float,
     *     cash_expenses: float,
     *     total_cash_in: float,
     *     total_transfer_in: float,
     *     total_collected: float,
     *     net_cash_handover: float,
     *     transactions_count: int,
     *     transactions: array<int, array<string, mixed>>,
     *     comparison: array<int, array<string, mixed>>
     * }
     */
    public function cashierShiftSummary(?int $cashierId, string $date, string $currency = 'YER'): array
    {
        $currency = strtoupper($currency);
        abort_unless(in_array($currency, ['YER', 'SAR'], true), 422);

        $tz = config('app.timezone', 'Asia/Aden');
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $fromDt = CarbonImmutable::parse($date, $tz)->startOfDay();
        $toExclusiveDt = CarbonImmutable::parse($date, $tz)->addDay()->startOfDay();

        $fromUtc = $isSqlite ? $fromDt->format('Y-m-d H:i:s') : $fromDt->utc();
        $toExclusiveUtc = $isSqlite ? $toExclusiveDt->format('Y-m-d H:i:s') : $toExclusiveDt->utc();

        $selectedUser = $cashierId ? User::find($cashierId) : null;
        $cashierName = $selectedUser ? ($selectedUser->name ?: $selectedUser->username) : 'كل المحاسبين والكاشيرات';

        // 1. Subscription Payments
        $subPayments = SubscriptionPayment::query()
            ->with(['creator', 'subscription.member'])
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->where('paid_at', '>=', $fromUtc)
            ->where('paid_at', '<', $toExclusiveUtc)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        // 2. Sales
        $sales = Sale::query()
            ->with(['creator', 'member'])
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->where('sold_at', '>=', $fromUtc)
            ->where('sold_at', '<', $toExclusiveUtc)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        // 3. Nutrition Payments
        $nutPayments = AppointmentPayment::query()
            ->with(['creator', 'appointment.member', 'appointment.nutritionClient'])
            ->where('currency', $currency)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $fromUtc)
            ->where('paid_at', '<', $toExclusiveUtc)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        // 4. Expenses
        $expenses = Expense::query()
            ->with(['creator', 'category'])
            ->where('currency', $currency)
            ->where('status', 'approved')
            ->whereDate('expense_date', $date)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        $subCash = round((float) $subPayments->where('payment_method', 'cash')->sum('amount'), 2);
        $subTransfer = round((float) $subPayments->where('payment_method', 'transfer')->sum('amount'), 2);

        $salesCash = round((float) $sales->where('payment_method', 'cash')->sum('total_amount'), 2);
        $salesTransfer = round((float) $sales->where('payment_method', 'transfer')->sum('total_amount'), 2);

        $nutCash = round((float) $nutPayments->where('payment_method', 'cash')->sum('amount'), 2);
        $nutTransfer = round((float) $nutPayments->where('payment_method', 'transfer')->sum('amount'), 2);

        $cashExpenses = round((float) $expenses->where('payment_method', 'cash')->sum('amount'), 2);

        $totalCashIn = round($subCash + $salesCash + $nutCash, 2);
        $totalTransferIn = round($subTransfer + $salesTransfer + $nutTransfer, 2);
        $totalCollected = round($totalCashIn + $totalTransferIn, 2);
        $netCashHandover = round(max(0, $totalCashIn - $cashExpenses), 2);

        // Build chronological list of transactions
        $transactions = collect();

        foreach ($subPayments as $p) {
            $transactions->push([
                'id' => 'SUB-'.$p->id,
                'time' => $p->paid_at ? Carbon::parse($p->paid_at)->timezone($tz) : now($tz),
                'time_formatted' => $p->paid_at ? Carbon::parse($p->paid_at)->timezone($tz)->format('h:i A') : '—',
                'type' => 'subscription',
                'type_label' => 'قسط اشتراك',
                'reference' => $p->receipt_number ?: ('SP-#'.$p->id),
                'client' => $p->subscription?->member->full_name ?? 'عضو نادي',
                'cashier' => $p->creator ? ($p->creator->name ?: $p->creator->username) : '—',
                'cashier_id' => $p->created_by,
                'method' => $p->payment_method,
                'method_label' => $p->payment_method === 'cash' ? 'نقدي' : 'تحويل بنكي',
                'transfer_details' => $p->payment_method === 'transfer' ? trim(($p->transfer_service ?: '').' '.($p->transfer_reference ?: '')) : null,
                'amount' => (float) $p->amount,
                'is_out' => false,
            ]);
        }

        foreach ($sales as $s) {
            $transactions->push([
                'id' => 'SALE-'.$s->id,
                'time' => $s->sold_at->timezone($tz),
                'time_formatted' => $s->sold_at->timezone($tz)->format('h:i A'),
                'type' => 'sale',
                'type_label' => 'مبيعات متجر',
                'reference' => $s->sale_number ?: ('S-#'.$s->id),
                'client' => $s->customer_name ?: ($s->member->full_name ?? 'زبون نقدي'),
                'cashier' => $s->creator ? ($s->creator->name ?: $s->creator->username) : '—',
                'cashier_id' => $s->created_by,
                'method' => $s->payment_method,
                'method_label' => $s->payment_method === 'cash' ? 'نقدي' : 'تحويل بنكي',
                'transfer_details' => $s->payment_method === 'transfer' ? trim(($s->transfer_service ?: '').' '.($s->transfer_reference ?: '')) : null,
                'amount' => (float) $s->total_amount,
                'is_out' => false,
            ]);
        }

        foreach ($nutPayments as $np) {
            $cName = $np->appointment ? ($np->appointment->member->full_name ?? ($np->appointment->nutritionClient->full_name ?? 'عميل تغذية')) : 'عميل تغذية';
            $transactions->push([
                'id' => 'NUT-'.$np->id,
                'time' => $np->paid_at ? Carbon::parse($np->paid_at)->timezone($tz) : now($tz),
                'time_formatted' => $np->paid_at ? Carbon::parse($np->paid_at)->timezone($tz)->format('h:i A') : '—',
                'type' => 'nutrition',
                'type_label' => 'جلسة تغذية',
                'reference' => 'NUT-#'.$np->id,
                'client' => $cName,
                'cashier' => $np->creator ? ($np->creator->name ?: $np->creator->username) : '—',
                'cashier_id' => $np->created_by,
                'method' => $np->payment_method,
                'method_label' => $np->payment_method === 'cash' ? 'نقدي' : 'تحويل بنكي',
                'transfer_details' => $np->payment_method === 'transfer' ? trim(($np->transfer_service ?: '').' '.($np->transfer_reference ?: '')) : null,
                'amount' => (float) $np->amount,
                'is_out' => false,
            ]);
        }

        foreach ($expenses as $e) {
            $transactions->push([
                'id' => 'EXP-'.$e->id,
                'time' => $e->created_at ? $e->created_at->timezone($tz) : now($tz),
                'time_formatted' => $e->created_at ? $e->created_at->timezone($tz)->format('h:i A') : '—',
                'type' => 'expense',
                'type_label' => 'مصروف صندوق',
                'reference' => 'EXP-#'.$e->id,
                'client' => $e->title ?: ($e->category ? $e->category->name : 'مصروف تشغيلي'),
                'cashier' => $e->creator ? ($e->creator->name ?: $e->creator->username) : '—',
                'cashier_id' => $e->created_by,
                'method' => $e->payment_method,
                'method_label' => $e->payment_method === 'cash' ? 'صرف نقدي' : 'تحويل',
                'transfer_details' => $e->transfer_service ? trim($e->transfer_service.' '.($e->transfer_reference ?: '')) : null,
                'amount' => (float) $e->amount,
                'is_out' => true,
            ]);
        }

        $sortedTransactions = $transactions->sortByDesc('time')->values()->all();

        // 5. Cashier comparison breakdown (for all active staff who processed anything today)
        $comparison = [];
        $activeStaff = User::query()->where('is_active', true)->orderBy('name')->get();

        foreach ($activeStaff as $staff) {
            $sSubs = SubscriptionPayment::query()
                ->where('currency', $currency)
                ->where('status', 'completed')
                ->where('paid_at', '>=', $fromUtc)
                ->where('paid_at', '<', $toExclusiveUtc)
                ->where('created_by', $staff->id)
                ->get();

            $sSales = Sale::query()
                ->where('currency', $currency)
                ->where('status', 'completed')
                ->where('sold_at', '>=', $fromUtc)
                ->where('sold_at', '<', $toExclusiveUtc)
                ->where('created_by', $staff->id)
                ->get();

            $sNut = AppointmentPayment::query()
                ->where('currency', $currency)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $fromUtc)
                ->where('paid_at', '<', $toExclusiveUtc)
                ->where('created_by', $staff->id)
                ->get();

            $sExp = Expense::query()
                ->where('currency', $currency)
                ->where('status', 'approved')
                ->whereDate('expense_date', $date)
                ->where('created_by', $staff->id)
                ->get();

            $sCashIn = round(
                (float) $sSubs->where('payment_method', 'cash')->sum('amount') +
                (float) $sSales->where('payment_method', 'cash')->sum('total_amount') +
                (float) $sNut->where('payment_method', 'cash')->sum('amount'),
                2
            );

            $sTransferIn = round(
                (float) $sSubs->where('payment_method', 'transfer')->sum('amount') +
                (float) $sSales->where('payment_method', 'transfer')->sum('total_amount') +
                (float) $sNut->where('payment_method', 'transfer')->sum('amount'),
                2
            );

            $sCashOut = round((float) $sExp->where('payment_method', 'cash')->sum('amount'), 2);
            $sNetHandover = round(max(0, $sCashIn - $sCashOut), 2);
            $sCount = $sSubs->count() + $sSales->count() + $sNut->count() + $sExp->count();

            if ($sCount > 0 || $staff->id === $cashierId) {
                $comparison[] = [
                    'id' => $staff->id,
                    'name' => $staff->name ?: $staff->username,
                    'username' => $staff->username,
                    'role' => match ($staff->role) {
                        'owner' => 'المالك',
                        'manager' => 'المدير',
                        'accountant' => 'المحاسب',
                        'reception' => 'الاستقبال',
                        'nutritionist' => 'اختصاصي تغذية',
                        default => $staff->role,
                    },
                    'period' => $staff->gymPeriod ? $staff->gymPeriod->name : ($staff->work_period === 'both' ? 'الفترتان' : ($staff->work_period === 'women' ? 'نساء' : 'رجال')),
                    'cash_in' => $sCashIn,
                    'transfer_in' => $sTransferIn,
                    'cash_out' => $sCashOut,
                    'net_handover' => $sNetHandover,
                    'transactions_count' => $sCount,
                ];
            }
        }

        return [
            'currency' => $currency,
            'date' => $date,
            'cashier_id' => $cashierId,
            'cashier_name' => $cashierName,
            'subscription_cash' => $subCash,
            'subscription_transfer' => $subTransfer,
            'nutrition_cash' => $nutCash,
            'nutrition_transfer' => $nutTransfer,
            'sales_cash' => $salesCash,
            'sales_transfer' => $salesTransfer,
            'cash_expenses' => $cashExpenses,
            'total_cash_in' => $totalCashIn,
            'total_transfer_in' => $totalTransferIn,
            'total_collected' => $totalCollected,
            'net_cash_handover' => $netCashHandover,
            'transactions_count' => count($sortedTransactions),
            'transactions' => $sortedTransactions,
            'comparison' => $comparison,
        ];
    }
}
