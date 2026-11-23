<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كشف الأعضاء والاشتراكات — WINNER GYM</title>
    {{-- WINNER GYM Favicon Suite with Cache-Buster --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=20261114" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20261114">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=20261114">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=20261114">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=20261114">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=20261114">
    <meta name="theme-color" content="#020813">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #e2e8f0;
            color: #0f172a;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            direction: rtl;
        }

        .no-print-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #0f172a;
            color: #f8fafc;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
        }

        .no-print-bar .title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 800;
        }

        .no-print-bar .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 700;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #1e293b;
            color: #e2e8f0;
            border: 1px solid #334155;
        }

        .btn-secondary:hover {
            background: #334155;
        }

        .sheet {
            max-width: {{ $options['orientation'] === 'portrait' ? '210mm' : '297mm' }};
            margin: 20px auto 36px auto;
            background: #ffffff;
            padding: 14mm 12mm;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
            border-radius: 4px;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 8px;
            margin: 12px 0 14px 0;
        }

        .kpi-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 7px 10px;
            background: #f8fafc;
            text-align: center;
        }

        .kpi-box span {
            display: block;
            font-size: 9.5px;
            color: #475569;
            font-weight: 700;
        }

        .kpi-box strong {
            display: block;
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 2px;
        }

        .notice-warn {
            margin: 8px 0 12px 0;
            padding: 8px 12px;
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .print-table thead {
            display: table-header-group;
        }

        .print-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            text-align: right;
            vertical-align: middle;
            font-size: 10.5px;
        }

        .print-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            white-space: nowrap;
        }

        .print-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 9.5px;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-green { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-orange { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .badge-red { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-blue { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        .badge-slate { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }

        .mono-ltr {
            direction: ltr;
            unicode-bidi: isolate;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 36px 16px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4 {{ $options['orientation'] === 'portrait' ? 'portrait' : 'landscape' }};
                margin: 10mm 9mm 12mm 9mm;
            }

            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .sheet {
                display: block !important;
                max-width: none !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .kpi-strip {
                display: table !important;
                width: 100% !important;
                table-layout: fixed !important;
                border-spacing: 6px 0 !important;
                margin: 8px -6px 10px -6px !important;
            }

            .kpi-box {
                display: table-cell !important;
            }

            .wg-report-footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    @php
        $memberStatusLabels = [
            'all' => 'الكل',
            'active' => 'نشط',
            'suspended' => 'موقوف',
            'archived' => 'مؤرشف',
        ];

        $subStatusLabels = [
            'all' => 'الكل',
            'active' => 'نشط',
            'expiring_soon' => 'ينتهي قريبًا',
            'financial_overdue' => 'متأخر ماليًا',
            'upcoming' => 'قادم',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد',
            'none' => 'بدون اشتراك',
        ];

        $periodLabels = [
            'all' => 'الكل (رجال ونساء)',
            'men' => 'فترة الرجال',
            'women' => 'فترة النساء',
        ];

        $sortLabels = [
            'latest' => 'الأحدث تسجيلاً',
            'name' => 'أبجدي (الاسم)',
            'end_date' => 'الأقرب لانتهاء الاشتراك',
        ];

        $toggleOrientation = $options['orientation'] === 'portrait' ? 'landscape' : 'portrait';
        $toggleUrl = route('members.print', array_merge(request()->query(), ['orientation' => $toggleOrientation, 'autoprint' => 0]));
    @endphp

    <div class="no-print-bar">
        <div class="title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            <span>معاينة طباعة كشف الأعضاء والاشتراكات ({{ number_format($summary['printed_count']) }} عضو)</span>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <span>طباعة الكشف (A4 {{ $options['orientation'] === 'portrait' ? 'عمودي' : 'أفقي' }})</span>
            </button>
            <a href="{{ $toggleUrl }}" class="btn btn-secondary">
                <span>تبديل إلى {{ $toggleOrientation === 'portrait' ? 'عمودي' : 'أفقي' }}</span>
            </a>
            <button type="button" class="btn btn-secondary" onclick="window.close()">إغلاق</button>
        </div>
    </div>

    <main class="sheet">
        @include('reports.partials.header', [
            'title' => 'كشف الأعضاء وحالاتهم واشتراكاتهم',
            'subtitle' => 'تقرير تفصيلي لأعضاء النادي وحالة العضوية والاشتراك الحالي والقادم وفق معايير الفلترة المحددة',
            'meta' => array_filter([
                'حالة العضو' => $memberStatusLabels[$filters['status']] ?? $filters['status'],
                'حالة الاشتراك' => $subStatusLabels[$filters['subscription_status']] ?? $filters['subscription_status'],
                'نوع الباقة' => $packageName ?? 'الكل',
                'الفترة' => $periodLabels[$filters['period']] ?? $filters['period'],
                'الترتيب' => $sortLabels[$filters['sort']] ?? $filters['sort'],
                'البحث' => $filters['search'] !== '' ? $filters['search'] : null,
                'تاريخ الطباعة' => $printedAt,
                'المستخدم' => $printedBy,
            ], fn ($v) => $v !== null && $v !== ''),
            'isPdf' => false,
        ])

        <section class="kpi-strip">
            <div class="kpi-box">
                <span>إجمالي المطابقين</span>
                <strong>{{ number_format($summary['total_matched']) }}</strong>
            </div>
            <div class="kpi-box">
                <span>أعضاء نشطون</span>
                <strong style="color:#166534;">{{ number_format($summary['active_members']) }}</strong>
            </div>
            <div class="kpi-box">
                <span>أعضاء موقوفون</span>
                <strong style="color:#9a3412;">{{ number_format($summary['suspended_members']) }}</strong>
            </div>
            <div class="kpi-box">
                <span>اشتراك نشط</span>
                <strong style="color:#1e40af;">{{ number_format($summary['active_subscriptions']) }}</strong>
            </div>
            <div class="kpi-box">
                <span>ينتهي قريبًا</span>
                <strong style="color:#b45309;">{{ number_format($summary['expiring_soon_subscriptions']) }}</strong>
            </div>
            <div class="kpi-box">
                <span>متأخر ماليًا</span>
                <strong style="color:#991b1b;">{{ number_format($summary['overdue_subscriptions']) }}</strong>
            </div>
            <div class="kpi-box">
                <span>منتهي / بدون اشتراك</span>
                <strong style="color:#475569;">{{ number_format($summary['expired_subscriptions'] + $summary['no_subscription_members']) }}</strong>
            </div>
        </section>

        @if($summary['truncated'])
            <div class="notice-warn">
                تنبيه: عدد النتائج المطابقة ({{ number_format($summary['total_matched']) }}) يتجاوز الحد الأقصى للطباعة الواحدة ({{ number_format(\App\Services\MemberDirectoryService::MAX_PRINT_RECORDS) }} عضو). تم عرض أول {{ number_format($summary['printed_count']) }} سجل؛ يُنصح بتضييق خيارات الفلترة لطباعة بقية السجلات.
            </div>
        @endif

        <table class="print-table">
            <thead>
                <tr>
                    <th style="width:32px;text-align:center;">#</th>
                    <th>الكود</th>
                    <th>اسم العضو</th>
                    @if($options['show_phone'])
                        <th>رقم الهاتف</th>
                    @endif
                    <th>الفترة</th>
                    <th>حالة العضو</th>
                    <th>الباقة الحالية</th>
                    <th>حالة الاشتراك</th>
                    @if($options['show_dates'])
                        <th>فترة الاشتراك</th>
                    @endif
                    @if($options['show_financial'])
                        <th>قيمة الباقة</th>
                    @endif
                    <th>الاشتراك القادم / ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $index => $member)
                    @php
                        $sub = $member->latestSubscription;
                        $upcoming = $member->upcomingSubscription;

                        $mBadge = match ($member->status) {
                            'active' => ['نشط', 'badge-green'],
                            'suspended' => ['موقوف', 'badge-orange'],
                            'archived' => ['مؤرشف', 'badge-slate'],
                            default => [$member->status, 'badge-slate'],
                        };

                        $sBadge = $sub ? match ($sub->status) {
                            'active' => ['نشط', 'badge-green'],
                            'expiring_soon' => ['ينتهي قريبًا', 'badge-orange'],
                            'financial_overdue' => ['متأخر ماليًا', 'badge-red'],
                            'upcoming' => ['قادم', 'badge-purple'],
                            'expired' => ['منتهي', 'badge-slate'],
                            'cancelled' => ['ملغي', 'badge-red'],
                            'refunded' => ['مسترد', 'badge-slate'],
                            default => [$sub->status, 'badge-slate'],
                        } : ['بدون اشتراك', 'badge-slate'];
                    @endphp
                    <tr>
                        <td style="text-align:center;font-weight:700;">{{ $index + 1 }}</td>
                        <td><span class="mono-ltr">{{ $member->membership_code }}</span></td>
                        <td style="font-weight:800;">{{ $member->full_name }}</td>
                        @if($options['show_phone'])
                            <td><span class="mono-ltr">{{ $member->phone }}</span></td>
                        @endif
                        <td>{{ $member->assigned_period === 'women' ? 'نساء' : 'رجال' }}</td>
                        <td><span class="badge {{ $mBadge[1] }}">{{ $mBadge[0] }}</span></td>
                        <td>{{ $sub ? $sub->package_name_snapshot : '—' }}</td>
                        <td><span class="badge {{ $sBadge[1] }}">{{ $sBadge[0] }}</span></td>
                        @if($options['show_dates'])
                            <td>
                                @if($sub)
                                    <span class="mono-ltr">{{ $sub->start_date->format('Y-m-d') }} → {{ $sub->end_date->format('Y-m-d') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        @if($options['show_financial'])
                            <td>
                                @if($sub && $sub->final_price !== null)
                                    <span class="mono-ltr">{{ \App\Support\NumberFormatter::money($sub->final_price) }} {{ $sub->currency }}</span>
                                    @if($sub->payment_plan === 'installments')
                                        <span class="badge badge-blue" style="margin-right:3px;">تقسيط</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        <td>
                            @if($upcoming)
                                <span class="badge badge-purple">قادم: {{ $upcoming->package_name_snapshot }} ({{ $upcoming->start_date->format('Y-m-d') }})</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="empty-state">لا توجد سجلات مطابقة لمعايير الفلترة المحددة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @include('reports.partials.footer', [
            'signEmployee' => $printedBy,
            'isPdf' => false,
        ])
    </main>

    @if($options['autoprint'])
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 300);
            });
        </script>
    @endif

</body>
</html>
