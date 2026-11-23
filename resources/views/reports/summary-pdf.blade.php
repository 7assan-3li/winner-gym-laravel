<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير WINNER GYM</title>
    <style>
        body {
            font-family: dejavusans, sans-serif;
            direction: rtl;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
        }
        .grid td, .grid th {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            font-size: 11px;
        }
        .grid th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: right;
        }
        .grid tr:nth-child(even) td {
            background: #f8fafc;
        }
        .num {
            text-align: left;
            direction: ltr;
            font-weight: 700;
        }
        .total-row td {
            background: #e2e8f0 !important;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #0f172a;
        }
    </style>
</head>
<body>

    @php
        $selectedStaff = !empty($filters['cashier_id']) ? \App\Models\User::find($filters['cashier_id']) : null;
        $staffName = $selectedStaff ? ($selectedStaff->name ?: $selectedStaff->username) : 'كل الموظفين والمحاسبين';
        $currLabel = ($filters['currency'] ?? '') === 'ALL' ? 'كل العملات (عزل كامل YER + SAR)' : ($filters['currency'] ?? 'YER');
        $isAll = !empty($data['is_all_currencies']);
    @endphp

    @include('reports.partials.header', [
        'title' => 'تقرير ملخص الإيرادات والعمليات المالي والتشغيلي',
        'subtitle' => 'ملخص شامل لكافة العمليات والإيرادات والمصروفات وتفصيل الصرافات خلال الفترة المحددة',
        'meta' => [
            'من تاريخ' => $filters['from'],
            'إلى تاريخ' => $filters['to'],
            'الفئة' => $filters['gender'] === 'male' ? 'رجال فقط' : ($filters['gender'] === 'female' ? 'نساء فقط' : 'الكل'),
            'العملة' => $currLabel,
            'قناة الدفع / الصرافة' => $data['payment_channel_label'] ?? 'الكل',
            'الموظف' => $staffName,
            'المستخدم المستخرج' => $user->name,
        ],
        'isPdf' => true,
    ])

    @if($isAll)
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:46%;">المؤشر / البيان المالي والتشغيلي</th>
                    <th style="width:27%;text-align:left;">ريال يمني (YER)</th>
                    <th style="width:27%;text-align:left;">ريال سعودي (SAR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'إيراد الاشتراكات (صافي بعد الاسترداد)' => 'subscription_revenue',
                    'إيراد استشارات وخطط التغذية' => 'nutrition_revenue',
                    'إيراد مبيعات المتجر' => 'product_revenue',
                    'تكلفة البضاعة المباعة (COGS)' => 'product_cogs',
                    'مجمل أرباح مبيعات المتجر' => 'product_profit',
                    'إجمالي المصروفات المنصرفة' => 'expenses',
                ] as $label => $key)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($data['yer'][$key]) }} YER</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($data['sar'][$key]) }} SAR</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>صافي الأرباح التشغيلية</td>
                    <td class="num" style="color: {{ $data['yer']['net'] >= 0 ? '#166534' : '#991b1b' }};">
                        {{ \App\Support\NumberFormatter::money($data['yer']['net']) }} YER
                    </td>
                    <td class="num" style="color: {{ $data['sar']['net'] >= 0 ? '#166534' : '#991b1b' }};">
                        {{ \App\Support\NumberFormatter::money($data['sar']['net']) }} SAR
                    </td>
                </tr>
                <tr>
                    <td>إجمالي حركات الحضور المسجلة</td>
                    <td class="num" colspan="2" style="text-align:center;">{{ number_format($data['attendance_count']) }} حضور</td>
                </tr>
                <tr>
                    <td>مواعيد وجلسات التغذية المنفذة</td>
                    <td class="num" colspan="2" style="text-align:center;">{{ number_format($data['appointments_count']) }} موعد</td>
                </tr>
                <tr>
                    <td>الأعضاء الجدد المسجلين خلال الفترة</td>
                    <td class="num" colspan="2" style="text-align:center;">{{ number_format($data['new_members_count']) }} عضو</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:65%;">المؤشر / البيان المالي والتشغيلي</th>
                    <th style="width:35%;text-align:left;">القيمة والعملة</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'إيراد الاشتراكات (صافي بعد الاسترداد)' => 'subscription_revenue',
                    'إيراد استشارات وخطط التغذية' => 'nutrition_revenue',
                    'إيراد مبيعات المتجر' => 'product_revenue',
                    'تكلفة البضاعة المباعة (COGS)' => 'product_cogs',
                    'مجمل أرباح مبيعات المتجر' => 'product_profit',
                    'إجمالي المصروفات المنصرفة' => 'expenses',
                ] as $label => $key)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($data[$key]) }} {{ $data['currency'] }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>صافي الأرباح التشغيلية</td>
                    <td class="num" style="color: {{ $data['net'] >= 0 ? '#166534' : '#991b1b' }};">
                        {{ \App\Support\NumberFormatter::money($data['net']) }} {{ $data['currency'] }}
                    </td>
                </tr>
                <tr>
                    <td>إجمالي حركات الحضور المسجلة</td>
                    <td class="num">{{ number_format($data['attendance_count']) }} حضور</td>
                </tr>
                <tr>
                    <td>مواعيد وجلسات التغذية المنفذة</td>
                    <td class="num">{{ number_format($data['appointments_count']) }} موعد</td>
                </tr>
                <tr>
                    <td>الأعضاء الجدد المسجلين خلال الفترة</td>
                    <td class="num">{{ number_format($data['new_members_count']) }} عضو</td>
                </tr>
            </tbody>
        </table>
    @endif

    <h3 style="font-size:12px;font-weight:bold;color:#0f172a;margin:16px 0 6px;">تفصيل الحركات المالية حسب الصرافة / قناة الدفع:</h3>
    @if($isAll)
        @php
            $yerPdfEx = collect($data['yer']['exchange_breakdown'] ?? [])->keyBy('key');
            $sarPdfEx = collect($data['sar']['exchange_breakdown'] ?? [])->keyBy('key');
            $allPdfKeys = $yerPdfEx->keys()->merge($sarPdfEx->keys())->unique();
        @endphp
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:28%;">قناة الدفع / الصرافة</th>
                    <th style="width:12%;text-align:left;">وارد (YER)</th>
                    <th style="width:12%;text-align:left;">صادر (YER)</th>
                    <th style="width:12%;text-align:left;">صافي (YER)</th>
                    <th style="width:12%;text-align:left;">وارد (SAR)</th>
                    <th style="width:12%;text-align:left;">صادر (SAR)</th>
                    <th style="width:12%;text-align:left;">صافي (SAR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allPdfKeys as $exKey)
                    @php
                        $yR = $yerPdfEx->get($exKey);
                        $sR = $sarPdfEx->get($exKey);
                        $lbl = $yR['label'] ?? ($sR['label'] ?? $exKey);
                    @endphp
                    <tr>
                        <td><strong>{{ $lbl }}</strong></td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($yR['in_amount'] ?? 0) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($yR['out_amount'] ?? 0) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($yR['net_amount'] ?? 0) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($sR['in_amount'] ?? 0) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($sR['out_amount'] ?? 0) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($sR['net_amount'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:36%;">قناة الدفع / الصرافة</th>
                    <th style="width:18%;text-align:left;">إجمالي الوارد ({{ $data['currency'] }})</th>
                    <th style="width:18%;text-align:left;">إجمالي الصادر ({{ $data['currency'] }})</th>
                    <th style="width:18%;text-align:left;">صافي الرصيد ({{ $data['currency'] }})</th>
                    <th style="width:10%;text-align:center;">العمليات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['exchange_breakdown'] ?? [] as $exRow)
                    <tr>
                        <td><strong>{{ $exRow['label'] }}</strong></td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($exRow['in_amount']) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($exRow['out_amount']) }}</td>
                        <td class="num">{{ \App\Support\NumberFormatter::money($exRow['net_amount']) }}</td>
                        <td style="text-align:center;font-weight:bold;">{{ $exRow['transactions_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @include('reports.partials.footer', [
        'signEmployee' => $user->name,
        'signManager' => 'إدارة الصالة / المدير العام',
        'isPdf' => true,
    ])

</body>
</html>
