<?php

namespace App\Services;

use Carbon\CarbonImmutable;
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
}
