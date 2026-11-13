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
    public function summary(string $from, string $to, string $gender = 'all', string $currency = 'YER', ?int $cashierId = null): array
    {
        $currency = strtoupper($currency);

        abort_unless(in_array($currency, ['YER', 'SAR', 'ALL'], true), 422);
        abort_unless(in_array($gender, ['all', 'male', 'female'], true), 422);

        if ($currency === 'ALL') {
            $yer = $this->summary($from, $to, $gender, 'YER', $cashierId);
            $sar = $this->summary($from, $to, $gender, 'SAR', $cashierId);

            return [
                'is_all_currencies' => true,
                'currency' => 'ALL',
                'yer' => $yer,
                'sar' => $sar,
                'subscription_gross_revenue' => 0.0,
                'subscription_refunds' => 0.0,
                'subscription_revenue' => 0.0,
                'nutrition_revenue' => 0.0,
                'product_revenue' => 0.0,
                'product_cogs' => 0.0,
                'product_profit' => 0.0,
                'expenses' => 0.0,
                'net' => 0.0,
                'attendance_count' => $yer['attendance_count'],
                'appointments_count' => $yer['appointments_count'],
                'new_members_count' => $yer['new_members_count'],
            ];
        }

        $tz = config('app.timezone', 'Asia/Aden');
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $fromDt = CarbonImmutable::parse($from, $tz)->startOfDay();
        $toExclusiveDt = CarbonImmutable::parse($to, $tz)->addDay()->startOfDay();

        $fromUtc = $isSqlite ? $fromDt->format('Y-m-d H:i:s') : $fromDt->utc();
        $toExclusiveUtc = $isSqlite ? $toExclusiveDt->format('Y-m-d H:i:s') : $toExclusiveDt->utc();

        $staffAll = $cashierId ? 'specific' : 'all';
        $staffId = $cashierId ?? 0;

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
                      and (:sub_staff_all = 'all' or p.created_by = :sub_staff_id)
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
                      and (:refund_staff_all = 'all' or r.processed_by = :refund_staff_id)
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
                      and (:nutrition_staff_all = 'all' or ap.created_by = :nutrition_staff_id)
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
                      and (:sales_staff_all = 'all' or s.created_by = :sales_staff_id)
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
                      and (:cogs_staff_all = 'all' or s.created_by = :cogs_staff_id)
                ), 0) as product_cogs,

                coalesce((
                    select sum(e.amount)
                    from expenses e
                    where e.currency = :expense_currency
                      and e.status = 'approved'
                      and e.expense_date between :expense_from and :expense_to
                      and (:expense_staff_all = 'all' or e.created_by = :expense_staff_id)
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
                'sub_staff_all' => $staffAll,
                'sub_staff_id' => $staffId,

                'refund_currency' => $currency,
                'refund_from' => $fromUtc,
                'refund_to' => $toExclusiveUtc,
                'refund_gender_all' => $gender,
                'refund_gender' => $gender,
                'refund_staff_all' => $staffAll,
                'refund_staff_id' => $staffId,

                'nutrition_currency' => $currency,
                'nutrition_from' => $fromUtc,
                'nutrition_to' => $toExclusiveUtc,
                'nutrition_gender_all' => $gender,
                'nutrition_gender' => $gender,
                'nutrition_staff_all' => $staffAll,
                'nutrition_staff_id' => $staffId,

                'sales_currency' => $currency,
                'sales_from' => $fromUtc,
                'sales_to' => $toExclusiveUtc,
                'sales_gender_all' => $gender,
                'sales_gender' => $gender,
                'sales_staff_all' => $staffAll,
                'sales_staff_id' => $staffId,

                'cogs_currency' => $currency,
                'cogs_from' => $fromUtc,
                'cogs_to' => $toExclusiveUtc,
                'cogs_gender_all' => $gender,
                'cogs_gender' => $gender,
                'cogs_staff_all' => $staffAll,
                'cogs_staff_id' => $staffId,

                'expense_currency' => $currency,
                'expense_from' => $from,
                'expense_to' => $to,
                'expense_staff_all' => $staffAll,
                'expense_staff_id' => $staffId,

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
            'is_all_currencies' => false,
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
        abort_unless(in_array($currency, ['YER', 'SAR', 'ALL'], true), 422);

        $tz = config('app.timezone', 'Asia/Aden');
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $fromDt = CarbonImmutable::parse($date, $tz)->startOfDay();
        $toExclusiveDt = CarbonImmutable::parse($date, $tz)->addDay()->startOfDay();

        $fromUtc = $isSqlite ? $fromDt->format('Y-m-d H:i:s') : $fromDt->utc();
        $toExclusiveUtc = $isSqlite ? $toExclusiveDt->format('Y-m-d H:i:s') : $toExclusiveDt->utc();

        $selectedUser = $cashierId ? User::find($cashierId) : null;
        $cashierName = $selectedUser ? ($selectedUser->name ?: $selectedUser->username) : 'كل المحاسبين والكاشيرات (إجمالي الصندوق)';

        $currencies = $currency === 'ALL' ? ['YER', 'SAR'] : [$currency];

        // 1. Subscription Payments
        $subPayments = SubscriptionPayment::query()
            ->with(['creator', 'subscription.member'])
            ->whereIn('currency', $currencies)
            ->where('status', 'completed')
            ->where('paid_at', '>=', $fromUtc)
            ->where('paid_at', '<', $toExclusiveUtc)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        // 2. Sales
        $sales = Sale::query()
            ->with(['creator', 'member'])
            ->whereIn('currency', $currencies)
            ->where('status', 'completed')
            ->where('sold_at', '>=', $fromUtc)
            ->where('sold_at', '<', $toExclusiveUtc)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        // 3. Nutrition Payments
        $nutPayments = AppointmentPayment::query()
            ->with(['creator', 'appointment.member', 'appointment.nutritionClient'])
            ->whereIn('currency', $currencies)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $fromUtc)
            ->where('paid_at', '<', $toExclusiveUtc)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        // 4. Expenses
        $expenses = Expense::query()
            ->with(['creator', 'category'])
            ->whereIn('currency', $currencies)
            ->where('status', 'approved')
            ->whereDate('expense_date', $date)
            ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
            ->get();

        $calcTotals = function (string $curr) use ($subPayments, $sales, $nutPayments, $expenses): array {
            $subC = round((float) $subPayments->where('currency', $curr)->where('payment_method', 'cash')->sum('amount'), 2);
            $subT = round((float) $subPayments->where('currency', $curr)->where('payment_method', 'transfer')->sum('amount'), 2);

            $salesC = round((float) $sales->where('currency', $curr)->where('payment_method', 'cash')->sum('total_amount'), 2);
            $salesT = round((float) $sales->where('currency', $curr)->where('payment_method', 'transfer')->sum('total_amount'), 2);

            $nutC = round((float) $nutPayments->where('currency', $curr)->where('payment_method', 'cash')->sum('amount'), 2);
            $nutT = round((float) $nutPayments->where('currency', $curr)->where('payment_method', 'transfer')->sum('amount'), 2);

            $cashExp = round((float) $expenses->where('currency', $curr)->where('payment_method', 'cash')->sum('amount'), 2);

            $totalCIn = round($subC + $salesC + $nutC, 2);
            $totalTIn = round($subT + $salesT + $nutT, 2);
            $totalCol = round($totalCIn + $totalTIn, 2);
            $netHand = round(max(0, $totalCIn - $cashExp), 2);

            return [
                'currency' => $curr,
                'subscription_cash' => $subC,
                'subscription_transfer' => $subT,
                'sales_cash' => $salesC,
                'sales_transfer' => $salesT,
                'nutrition_cash' => $nutC,
                'nutrition_transfer' => $nutT,
                'cash_expenses' => $cashExp,
                'total_cash_in' => $totalCIn,
                'total_transfer_in' => $totalTIn,
                'total_collected' => $totalCol,
                'net_cash_handover' => $netHand,
            ];
        };

        $yerTotals = $calcTotals('YER');
        $sarTotals = $calcTotals('SAR');

        $activeTotals = $currency === 'SAR' ? $sarTotals : $yerTotals;

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
                'currency' => $p->currency,
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
                'currency' => $s->currency,
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
                'currency' => $np->currency,
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
                'currency' => $e->currency,
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
            $sSubs = $subPayments->where('created_by', $staff->id);
            $sSales = $sales->where('created_by', $staff->id);
            $sNut = $nutPayments->where('created_by', $staff->id);
            $sExp = $expenses->where('created_by', $staff->id);

            $sCashInYer = round(
                (float) $sSubs->where('currency', 'YER')->where('payment_method', 'cash')->sum('amount') +
                (float) $sSales->where('currency', 'YER')->where('payment_method', 'cash')->sum('total_amount') +
                (float) $sNut->where('currency', 'YER')->where('payment_method', 'cash')->sum('amount'),
                2
            );
            $sTransferInYer = round(
                (float) $sSubs->where('currency', 'YER')->where('payment_method', 'transfer')->sum('amount') +
                (float) $sSales->where('currency', 'YER')->where('payment_method', 'transfer')->sum('total_amount') +
                (float) $sNut->where('currency', 'YER')->where('payment_method', 'transfer')->sum('amount'),
                2
            );
            $sCashOutYer = round((float) $sExp->where('currency', 'YER')->where('payment_method', 'cash')->sum('amount'), 2);
            $sNetHandoverYer = round(max(0, $sCashInYer - $sCashOutYer), 2);

            $sCashInSar = round(
                (float) $sSubs->where('currency', 'SAR')->where('payment_method', 'cash')->sum('amount') +
                (float) $sSales->where('currency', 'SAR')->where('payment_method', 'cash')->sum('total_amount') +
                (float) $sNut->where('currency', 'SAR')->where('payment_method', 'cash')->sum('amount'),
                2
            );
            $sTransferInSar = round(
                (float) $sSubs->where('currency', 'SAR')->where('payment_method', 'transfer')->sum('amount') +
                (float) $sSales->where('currency', 'SAR')->where('payment_method', 'transfer')->sum('total_amount') +
                (float) $sNut->where('currency', 'SAR')->where('payment_method', 'transfer')->sum('amount'),
                2
            );
            $sCashOutSar = round((float) $sExp->where('currency', 'SAR')->where('payment_method', 'cash')->sum('amount'), 2);
            $sNetHandoverSar = round(max(0, $sCashInSar - $sCashOutSar), 2);

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
                    'cash_in' => $currency === 'SAR' ? $sCashInSar : $sCashInYer,
                    'transfer_in' => $currency === 'SAR' ? $sTransferInSar : $sTransferInYer,
                    'cash_out' => $currency === 'SAR' ? $sCashOutSar : $sCashOutYer,
                    'net_handover' => $currency === 'SAR' ? $sNetHandoverSar : $sNetHandoverYer,
                    'cash_in_yer' => $sCashInYer,
                    'transfer_in_yer' => $sTransferInYer,
                    'cash_out_yer' => $sCashOutYer,
                    'net_handover_yer' => $sNetHandoverYer,
                    'cash_in_sar' => $sCashInSar,
                    'transfer_in_sar' => $sTransferInSar,
                    'cash_out_sar' => $sCashOutSar,
                    'net_handover_sar' => $sNetHandoverSar,
                    'transactions_count' => $sCount,
                ];
            }
        }

        return [
            'is_all_currencies' => $currency === 'ALL',
            'currency' => $currency,
            'date' => $date,
            'cashier_id' => $cashierId,
            'cashier_name' => $cashierName,
            'subscription_cash' => $activeTotals['subscription_cash'],
            'subscription_transfer' => $activeTotals['subscription_transfer'],
            'nutrition_cash' => $activeTotals['nutrition_cash'],
            'nutrition_transfer' => $activeTotals['nutrition_transfer'],
            'sales_cash' => $activeTotals['sales_cash'],
            'sales_transfer' => $activeTotals['sales_transfer'],
            'cash_expenses' => $activeTotals['cash_expenses'],
            'total_cash_in' => $activeTotals['total_cash_in'],
            'total_transfer_in' => $activeTotals['total_transfer_in'],
            'total_collected' => $activeTotals['total_collected'],
            'net_cash_handover' => $activeTotals['net_cash_handover'],
            'yer' => $yerTotals,
            'sar' => $sarTotals,
            'transactions_count' => count($sortedTransactions),
            'transactions' => $sortedTransactions,
            'comparison' => $comparison,
        ];
    }
}
