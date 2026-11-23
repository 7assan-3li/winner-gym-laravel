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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** @var array<int, string> */
    public const STANDARD_TRANSFER_SERVICES = ['العمقي', 'الكريمي', 'البسيري'];

    /**
     * Return standard transfer services merged with any distinct historical service names stored in the database.
     *
     * @return array<int, string>
     */
    public function availableTransferServices(): array
    {
        $rows = DB::select(
            <<<'SQL'
            select distinct transfer_service from (
                select transfer_service from subscription_payments where transfer_service is not null and transfer_service <> ''
                union
                select transfer_service from subscription_refunds where transfer_service is not null and transfer_service <> ''
                union
                select transfer_service from sales where transfer_service is not null and transfer_service <> ''
                union
                select transfer_service from appointment_payments where transfer_service is not null and transfer_service <> ''
                union
                select transfer_service from expenses where transfer_service is not null and transfer_service <> ''
            ) as services
            order by transfer_service
            SQL
        );

        $dynamic = array_map(static fn (object $r): string => trim((string) $r->transfer_service), $rows);

        return array_values(array_unique(array_filter(array_merge(self::STANDARD_TRANSFER_SERVICES, $dynamic))));
    }

    /**
     * @return array{mode: string, service: string|null, raw: string, label: string}
     */
    public function parsePaymentChannel(?string $channel): array
    {
        $raw = trim((string) ($channel ?? 'all'));
        if ($raw === '' || $raw === 'all') {
            return [
                'mode' => 'all',
                'service' => null,
                'raw' => 'all',
                'label' => 'الكل (نقدي + جميع الصرافات)',
            ];
        }

        if ($raw === 'cash') {
            return [
                'mode' => 'cash',
                'service' => null,
                'raw' => 'cash',
                'label' => 'نقدي فقط (كاش الصندوق)',
            ];
        }

        if ($raw === 'transfer') {
            return [
                'mode' => 'transfer',
                'service' => null,
                'raw' => 'transfer',
                'label' => 'جميع التحويلات المصرفية (كل الصرافات)',
            ];
        }

        if (str_starts_with($raw, 'service:')) {
            $service = trim(substr($raw, 8));
            if ($service !== '') {
                return [
                    'mode' => 'service',
                    'service' => $service,
                    'raw' => 'service:'.$service,
                    'label' => 'صرافة / تحويل: '.$service,
                ];
            }
        }

        return [
            'mode' => 'all',
            'service' => null,
            'raw' => 'all',
            'label' => 'الكل (نقدي + جميع الصرافات)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(
        string $from,
        string $to,
        string $gender = 'all',
        string $currency = 'YER',
        ?int $cashierId = null,
        string $paymentChannel = 'all',
    ): array {
        $currency = strtoupper($currency);
        $channel = $this->parsePaymentChannel($paymentChannel);

        abort_unless(in_array($currency, ['YER', 'SAR', 'ALL'], true), 422);
        abort_unless(in_array($gender, ['all', 'male', 'female'], true), 422);

        if ($currency === 'ALL') {
            $yer = $this->summary($from, $to, $gender, 'YER', $cashierId, $channel['raw']);
            $sar = $this->summary($from, $to, $gender, 'SAR', $cashierId, $channel['raw']);

            return [
                'is_all_currencies' => true,
                'currency' => 'ALL',
                'payment_channel' => $channel['raw'],
                'payment_channel_label' => $channel['label'],
                'yer' => $yer,
                'sar' => $sar,
                'exchange_breakdown' => [],
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
        $channelMode = $channel['mode'];
        $channelService = $channel['service'] ?? '';

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
                      and (
                          :sub_ch_mode = 'all'
                          or (:sub_ch_mode = 'cash' and p.payment_method = 'cash')
                          or (:sub_ch_mode = 'transfer' and p.payment_method = 'transfer')
                          or (:sub_ch_mode = 'service' and p.payment_method = 'transfer' and coalesce(p.transfer_service, '') = :sub_ch_service)
                      )
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
                      and (
                          :refund_ch_mode = 'all'
                          or (:refund_ch_mode = 'cash' and r.payment_method = 'cash')
                          or (:refund_ch_mode = 'transfer' and r.payment_method = 'transfer')
                          or (:refund_ch_mode = 'service' and r.payment_method = 'transfer' and coalesce(r.transfer_service, '') = :refund_ch_service)
                      )
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
                      and (
                          :nut_ch_mode = 'all'
                          or (:nut_ch_mode = 'cash' and ap.payment_method = 'cash')
                          or (:nut_ch_mode = 'transfer' and ap.payment_method = 'transfer')
                          or (:nut_ch_mode = 'service' and ap.payment_method = 'transfer' and coalesce(ap.transfer_service, '') = :nut_ch_service)
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
                      and (:sales_staff_all = 'all' or s.created_by = :sales_staff_id)
                      and (
                          :sales_ch_mode = 'all'
                          or (:sales_ch_mode = 'cash' and s.payment_method = 'cash')
                          or (:sales_ch_mode = 'transfer' and s.payment_method = 'transfer')
                          or (:sales_ch_mode = 'service' and s.payment_method = 'transfer' and coalesce(s.transfer_service, '') = :sales_ch_service)
                      )
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
                      and (
                          :cogs_ch_mode = 'all'
                          or (:cogs_ch_mode = 'cash' and s.payment_method = 'cash')
                          or (:cogs_ch_mode = 'transfer' and s.payment_method = 'transfer')
                          or (:cogs_ch_mode = 'service' and s.payment_method = 'transfer' and coalesce(s.transfer_service, '') = :cogs_ch_service)
                      )
                ), 0) as product_cogs,

                coalesce((
                    select sum(e.amount)
                    from expenses e
                    where e.currency = :expense_currency
                      and e.status = 'approved'
                      and e.expense_date between :expense_from and :expense_to
                      and (:expense_staff_all = 'all' or e.created_by = :expense_staff_id)
                      and (
                          :exp_ch_mode = 'all'
                          or (:exp_ch_mode = 'cash' and e.payment_method = 'cash')
                          or (:exp_ch_mode = 'transfer' and e.payment_method = 'transfer')
                          or (:exp_ch_mode = 'service' and e.payment_method = 'transfer' and coalesce(e.transfer_service, '') = :exp_ch_service)
                      )
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
                'sub_ch_mode' => $channelMode,
                'sub_ch_service' => $channelService,

                'refund_currency' => $currency,
                'refund_from' => $fromUtc,
                'refund_to' => $toExclusiveUtc,
                'refund_gender_all' => $gender,
                'refund_gender' => $gender,
                'refund_staff_all' => $staffAll,
                'refund_staff_id' => $staffId,
                'refund_ch_mode' => $channelMode,
                'refund_ch_service' => $channelService,

                'nutrition_currency' => $currency,
                'nutrition_from' => $fromUtc,
                'nutrition_to' => $toExclusiveUtc,
                'nutrition_gender_all' => $gender,
                'nutrition_gender' => $gender,
                'nutrition_staff_all' => $staffAll,
                'nutrition_staff_id' => $staffId,
                'nut_ch_mode' => $channelMode,
                'nut_ch_service' => $channelService,

                'sales_currency' => $currency,
                'sales_from' => $fromUtc,
                'sales_to' => $toExclusiveUtc,
                'sales_gender_all' => $gender,
                'sales_gender' => $gender,
                'sales_staff_all' => $staffAll,
                'sales_staff_id' => $staffId,
                'sales_ch_mode' => $channelMode,
                'sales_ch_service' => $channelService,

                'cogs_currency' => $currency,
                'cogs_from' => $fromUtc,
                'cogs_to' => $toExclusiveUtc,
                'cogs_gender_all' => $gender,
                'cogs_gender' => $gender,
                'cogs_staff_all' => $staffAll,
                'cogs_staff_id' => $staffId,
                'cogs_ch_mode' => $channelMode,
                'cogs_ch_service' => $channelService,

                'expense_currency' => $currency,
                'expense_from' => $from,
                'expense_to' => $to,
                'expense_staff_all' => $staffAll,
                'expense_staff_id' => $staffId,
                'exp_ch_mode' => $channelMode,
                'exp_ch_service' => $channelService,

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

        $exchangeBreakdown = $this->buildPeriodExchangeBreakdown(
            $from,
            $to,
            $fromUtc,
            $toExclusiveUtc,
            $gender,
            $currency,
            $cashierId,
            $channelMode,
            $channelService
        );

        return [
            'is_all_currencies' => false,
            'currency' => $currency,
            'payment_channel' => $channel['raw'],
            'payment_channel_label' => $channel['label'],
            'exchange_breakdown' => $exchangeBreakdown,
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
     * Build exchange / payment channel breakdown for a date range and single currency.
     *
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     is_cash: bool,
     *     in_amount: float,
     *     out_amount: float,
     *     net_amount: float,
     *     transactions_count: int
     * }>
     */
    private function buildPeriodExchangeBreakdown(
        string $from,
        string $to,
        mixed $fromUtc,
        mixed $toExclusiveUtc,
        string $gender,
        string $currency,
        ?int $cashierId,
        string $channelMode,
        string $channelService,
    ): array {
        $staffAll = $cashierId ? 'specific' : 'all';
        $staffId = $cashierId ?? 0;

        $rows = DB::select(
            <<<'SQL'
            select
                channel_type,
                channel_name,
                sum(in_amount) as total_in,
                sum(out_amount) as total_out,
                sum(tx_count) as total_count
            from (
                select
                    p.payment_method as channel_type,
                    coalesce(nullif(trim(p.transfer_service), ''), 'غير محدد') as channel_name,
                    sum(p.amount) as in_amount,
                    0 as out_amount,
                    count(*) as tx_count
                from subscription_payments p
                join subscriptions s on s.id = p.subscription_id
                join members m on m.id = s.member_id
                where p.currency = :sub_c and p.status = 'completed'
                  and p.paid_at >= :sub_f and p.paid_at < :sub_t
                  and (:sub_ga = 'all' or m.gender = :sub_g)
                  and (:sub_sa = 'all' or p.created_by = :sub_si)
                group by p.payment_method, coalesce(nullif(trim(p.transfer_service), ''), 'غير محدد')

                union all

                select
                    s.payment_method as channel_type,
                    coalesce(nullif(trim(s.transfer_service), ''), 'غير محدد') as channel_name,
                    sum(s.total_amount) as in_amount,
                    0 as out_amount,
                    count(*) as tx_count
                from sales s
                left join members m on m.id = s.member_id
                where s.currency = :sal_c and s.status = 'completed'
                  and s.sold_at >= :sal_f and s.sold_at < :sal_t
                  and (:sal_ga = 'all' or m.gender = :sal_g)
                  and (:sal_sa = 'all' or s.created_by = :sal_si)
                group by s.payment_method, coalesce(nullif(trim(s.transfer_service), ''), 'غير محدد')

                union all

                select
                    ap.payment_method as channel_type,
                    coalesce(nullif(trim(ap.transfer_service), ''), 'غير محدد') as channel_name,
                    sum(ap.amount) as in_amount,
                    0 as out_amount,
                    count(*) as tx_count
                from appointment_payments ap
                join appointments a on a.id = ap.appointment_id
                left join members m on m.id = a.member_id
                left join nutrition_clients nc on nc.id = a.nutrition_client_id
                where ap.currency = :nut_c and ap.status = 'paid'
                  and ap.paid_at >= :nut_f and ap.paid_at < :nut_t
                  and (:nut_ga = 'all' or coalesce(m.gender, nc.gender) = :nut_g)
                  and (:nut_sa = 'all' or ap.created_by = :nut_si)
                group by ap.payment_method, coalesce(nullif(trim(ap.transfer_service), ''), 'غير محدد')

                union all

                select
                    r.payment_method as channel_type,
                    coalesce(nullif(trim(r.transfer_service), ''), 'غير محدد') as channel_name,
                    0 as in_amount,
                    sum(r.amount) as out_amount,
                    count(*) as tx_count
                from subscription_refunds r
                join subscriptions s on s.id = r.subscription_id
                join members m on m.id = s.member_id
                where r.currency = :ref_c and r.status = 'completed'
                  and r.processed_at >= :ref_f and r.processed_at < :ref_t
                  and (:ref_ga = 'all' or m.gender = :ref_g)
                  and (:ref_sa = 'all' or r.processed_by = :ref_si)
                group by r.payment_method, coalesce(nullif(trim(r.transfer_service), ''), 'غير محدد')

                union all

                select
                    e.payment_method as channel_type,
                    coalesce(nullif(trim(e.transfer_service), ''), 'غير محدد') as channel_name,
                    0 as in_amount,
                    sum(e.amount) as out_amount,
                    count(*) as tx_count
                from expenses e
                where e.currency = :exp_c and e.status = 'approved'
                  and e.expense_date between :exp_f and :exp_t
                  and (:exp_sa = 'all' or e.created_by = :exp_si)
                group by e.payment_method, coalesce(nullif(trim(e.transfer_service), ''), 'غير محدد')
            ) as combined
            group by channel_type, channel_name
            SQL,
            [
                'sub_c' => $currency, 'sub_f' => $fromUtc, 'sub_t' => $toExclusiveUtc, 'sub_ga' => $gender, 'sub_g' => $gender, 'sub_sa' => $staffAll, 'sub_si' => $staffId,
                'sal_c' => $currency, 'sal_f' => $fromUtc, 'sal_t' => $toExclusiveUtc, 'sal_ga' => $gender, 'sal_g' => $gender, 'sal_sa' => $staffAll, 'sal_si' => $staffId,
                'nut_c' => $currency, 'nut_f' => $fromUtc, 'nut_t' => $toExclusiveUtc, 'nut_ga' => $gender, 'nut_g' => $gender, 'nut_sa' => $staffAll, 'nut_si' => $staffId,
                'ref_c' => $currency, 'ref_f' => $fromUtc, 'ref_t' => $toExclusiveUtc, 'ref_ga' => $gender, 'ref_g' => $gender, 'ref_sa' => $staffAll, 'ref_si' => $staffId,
                'exp_c' => $currency, 'exp_f' => $from, 'exp_t' => $to, 'exp_sa' => $staffAll, 'exp_si' => $staffId,
            ]
        );

        return $this->formatExchangeBreakdownRows($rows, $channelMode, $channelService);
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     is_cash: bool,
     *     in_amount: float,
     *     out_amount: float,
     *     net_amount: float,
     *     transactions_count: int
     * }>
     */
    private function formatExchangeBreakdownRows(array $rows, string $channelMode, string $channelService): array
    {
        $map = [
            'cash' => [
                'key' => 'cash',
                'label' => 'نقدي (كاش الصندوق)',
                'is_cash' => true,
                'in_amount' => 0.0,
                'out_amount' => 0.0,
                'net_amount' => 0.0,
                'transactions_count' => 0,
            ],
        ];

        foreach (self::STANDARD_TRANSFER_SERVICES as $srv) {
            $map['service:'.$srv] = [
                'key' => 'service:'.$srv,
                'label' => 'صرافة / تحويل: '.$srv,
                'is_cash' => false,
                'in_amount' => 0.0,
                'out_amount' => 0.0,
                'net_amount' => 0.0,
                'transactions_count' => 0,
            ];
        }

        foreach ($rows as $r) {
            $type = (string) $r->channel_type;
            $name = trim((string) $r->channel_name);
            $key = $type === 'cash' ? 'cash' : 'service:'.$name;

            if (! isset($map[$key])) {
                $map[$key] = [
                    'key' => $key,
                    'label' => $type === 'cash' ? 'نقدي (كاش الصندوق)' : 'صرافة / تحويل: '.$name,
                    'is_cash' => $type === 'cash',
                    'in_amount' => 0.0,
                    'out_amount' => 0.0,
                    'net_amount' => 0.0,
                    'transactions_count' => 0,
                ];
            }

            $map[$key]['in_amount'] = round($map[$key]['in_amount'] + (float) $r->total_in, 2);
            $map[$key]['out_amount'] = round($map[$key]['out_amount'] + (float) $r->total_out, 2);
            $map[$key]['net_amount'] = round($map[$key]['in_amount'] - $map[$key]['out_amount'], 2);
            $map[$key]['transactions_count'] += (int) $r->total_count;
        }

        return array_values(array_filter($map, static function (array $item) use ($channelMode, $channelService): bool {
            if ($channelMode === 'cash') {
                return $item['is_cash'];
            }
            if ($channelMode === 'transfer') {
                return ! $item['is_cash'];
            }
            if ($channelMode === 'service') {
                return $item['key'] === 'service:'.$channelService;
            }

            return true;
        }));
    }

    /**
     * Cashier Shift Reconciliation & Cash Handover Summary
     *
     * @return array<string, mixed>
     */
    public function cashierShiftSummary(
        ?int $cashierId,
        string $date,
        string $currency = 'YER',
        string $paymentChannel = 'all',
        string $transactionType = 'all',
        string $search = '',
    ): array {
        $currency = strtoupper($currency);
        abort_unless(in_array($currency, ['YER', 'SAR', 'ALL'], true), 422);

        $channel = $this->parsePaymentChannel($paymentChannel);
        $transactionType = in_array($transactionType, ['all', 'subscription', 'sale', 'nutrition', 'expense'], true)
            ? $transactionType
            : 'all';
        $search = trim($search);

        $tz = config('app.timezone', 'Asia/Aden');
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $fromDt = CarbonImmutable::parse($date, $tz)->startOfDay();
        $toExclusiveDt = CarbonImmutable::parse($date, $tz)->addDay()->startOfDay();

        $fromUtc = $isSqlite ? $fromDt->format('Y-m-d H:i:s') : $fromDt->utc();
        $toExclusiveUtc = $isSqlite ? $toExclusiveDt->format('Y-m-d H:i:s') : $toExclusiveDt->utc();

        $selectedUser = $cashierId ? User::find($cashierId) : null;
        $cashierName = $selectedUser ? ($selectedUser->name ?: $selectedUser->username) : 'كل المحاسبين والكاشيرات (إجمالي الصندوق)';

        $currencies = $currency === 'ALL' ? ['YER', 'SAR'] : [$currency];

        $applyChannelScope = static function (Builder $q) use ($channel): void {
            if ($channel['mode'] === 'cash') {
                $q->where('payment_method', 'cash');
            } elseif ($channel['mode'] === 'transfer') {
                $q->where('payment_method', 'transfer');
            } elseif ($channel['mode'] === 'service' && $channel['service'] !== null) {
                $q->where('payment_method', 'transfer')
                    ->where('transfer_service', $channel['service']);
            }
        };

        // 1. Subscription Payments
        $subPayments = in_array($transactionType, ['all', 'subscription'], true)
            ? SubscriptionPayment::query()
                ->with(['creator', 'subscription.member'])
                ->whereIn('currency', $currencies)
                ->where('status', 'completed')
                ->where('paid_at', '>=', $fromUtc)
                ->where('paid_at', '<', $toExclusiveUtc)
                ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
                ->tap($applyChannelScope)
                ->get()
            : collect();

        // 2. Sales
        $sales = in_array($transactionType, ['all', 'sale'], true)
            ? Sale::query()
                ->with(['creator', 'member'])
                ->whereIn('currency', $currencies)
                ->where('status', 'completed')
                ->where('sold_at', '>=', $fromUtc)
                ->where('sold_at', '<', $toExclusiveUtc)
                ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
                ->tap($applyChannelScope)
                ->get()
            : collect();

        // 3. Nutrition Payments
        $nutPayments = in_array($transactionType, ['all', 'nutrition'], true)
            ? AppointmentPayment::query()
                ->with(['creator', 'appointment.member', 'appointment.nutritionClient'])
                ->whereIn('currency', $currencies)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $fromUtc)
                ->where('paid_at', '<', $toExclusiveUtc)
                ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
                ->tap($applyChannelScope)
                ->get()
            : collect();

        // 4. Expenses
        $expenses = in_array($transactionType, ['all', 'expense'], true)
            ? Expense::query()
                ->with(['creator', 'category'])
                ->whereIn('currency', $currencies)
                ->where('status', 'approved')
                ->whereDate('expense_date', $date)
                ->when($cashierId, fn (Builder $q) => $q->where('created_by', $cashierId))
                ->tap($applyChannelScope)
                ->get()
            : collect();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $matches = static fn (?string ...$fields): bool => collect($fields)
                ->contains(fn (?string $f): bool => $f !== null && $f !== '' && str_contains(mb_strtolower($f), $needle));

            $subPayments = $subPayments->filter(fn (SubscriptionPayment $p): bool => $matches(
                $p->receipt_number,
                'SP-#'.$p->id,
                $p->transfer_reference,
                $p->transfer_service,
                $p->subscription?->member?->full_name
            ))->values();

            $sales = $sales->filter(fn (Sale $s): bool => $matches(
                $s->sale_number,
                'S-#'.$s->id,
                $s->transfer_reference,
                $s->transfer_service,
                $s->customer_name,
                $s->member?->full_name
            ))->values();

            $nutPayments = $nutPayments->filter(fn (AppointmentPayment $np): bool => $matches(
                'NUT-#'.$np->id,
                $np->transfer_reference,
                $np->transfer_service,
                $np->appointment?->member?->full_name,
                $np->appointment?->nutritionClient?->full_name
            ))->values();

            $expenses = $expenses->filter(fn (Expense $e): bool => $matches(
                'EXP-#'.$e->id,
                $e->transfer_reference,
                $e->transfer_service,
                $e->title,
                $e->category?->name
            ))->values();
        }

        $buildShiftExchangeBreakdown = function (
            Collection $currSubs,
            Collection $currSales,
            Collection $currNut,
            Collection $currExp,
        ) use ($channel): array {
            $rawRows = [];

            $pushRow = static function (string $method, ?string $service, float $in, float $out) use (&$rawRows): void {
                $rawRows[] = (object) [
                    'channel_type' => $method,
                    'channel_name' => trim((string) $service) !== '' ? trim((string) $service) : 'غير محدد',
                    'total_in' => $in,
                    'total_out' => $out,
                    'total_count' => 1,
                ];
            };

            foreach ($currSubs as $p) {
                $pushRow((string) $p->payment_method, $p->transfer_service, (float) $p->amount, 0.0);
            }
            foreach ($currSales as $s) {
                $pushRow((string) $s->payment_method, $s->transfer_service, (float) $s->total_amount, 0.0);
            }
            foreach ($currNut as $np) {
                $pushRow((string) $np->payment_method, $np->transfer_service, (float) $np->amount, 0.0);
            }
            foreach ($currExp as $e) {
                $pushRow((string) $e->payment_method, $e->transfer_service, 0.0, (float) $e->amount);
            }

            return $this->formatExchangeBreakdownRows($rawRows, $channel['mode'], $channel['service'] ?? '');
        };

        $calcTotals = function (string $curr) use ($subPayments, $sales, $nutPayments, $expenses, $buildShiftExchangeBreakdown): array {
            $cSubs = $subPayments->where('currency', $curr);
            $cSales = $sales->where('currency', $curr);
            $cNut = $nutPayments->where('currency', $curr);
            $cExp = $expenses->where('currency', $curr);

            $subC = round((float) $cSubs->where('payment_method', 'cash')->sum('amount'), 2);
            $subT = round((float) $cSubs->where('payment_method', 'transfer')->sum('amount'), 2);

            $salesC = round((float) $cSales->where('payment_method', 'cash')->sum('total_amount'), 2);
            $salesT = round((float) $cSales->where('payment_method', 'transfer')->sum('total_amount'), 2);

            $nutC = round((float) $cNut->where('payment_method', 'cash')->sum('amount'), 2);
            $nutT = round((float) $cNut->where('payment_method', 'transfer')->sum('amount'), 2);

            $cashExp = round((float) $cExp->where('payment_method', 'cash')->sum('amount'), 2);
            $transferExp = round((float) $cExp->where('payment_method', 'transfer')->sum('amount'), 2);

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
                'transfer_expenses' => $transferExp,
                'total_cash_in' => $totalCIn,
                'total_transfer_in' => $totalTIn,
                'total_collected' => $totalCol,
                'net_cash_handover' => $netHand,
                'exchange_breakdown' => $buildShiftExchangeBreakdown($cSubs, $cSales, $cNut, $cExp),
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
                'transfer_service' => $p->transfer_service,
                'transfer_reference' => $p->transfer_reference,
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
                'transfer_service' => $s->transfer_service,
                'transfer_reference' => $s->transfer_reference,
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
                'transfer_service' => $np->transfer_service,
                'transfer_reference' => $np->transfer_reference,
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
                'transfer_service' => $e->transfer_service,
                'transfer_reference' => $e->transfer_reference,
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
            'payment_channel' => $channel['raw'],
            'payment_channel_label' => $channel['label'],
            'transaction_type' => $transactionType,
            'search' => $search,
            'subscription_cash' => $activeTotals['subscription_cash'],
            'subscription_transfer' => $activeTotals['subscription_transfer'],
            'nutrition_cash' => $activeTotals['nutrition_cash'],
            'nutrition_transfer' => $activeTotals['nutrition_transfer'],
            'sales_cash' => $activeTotals['sales_cash'],
            'sales_transfer' => $activeTotals['sales_transfer'],
            'cash_expenses' => $activeTotals['cash_expenses'],
            'transfer_expenses' => $activeTotals['transfer_expenses'],
            'total_cash_in' => $activeTotals['total_cash_in'],
            'total_transfer_in' => $activeTotals['total_transfer_in'],
            'total_collected' => $activeTotals['total_collected'],
            'net_cash_handover' => $activeTotals['net_cash_handover'],
            'exchange_breakdown' => $activeTotals['exchange_breakdown'],
            'yer' => $yerTotals,
            'sar' => $sarTotals,
            'transactions_count' => count($sortedTransactions),
            'transactions' => $sortedTransactions,
            'comparison' => $comparison,
        ];
    }
}
