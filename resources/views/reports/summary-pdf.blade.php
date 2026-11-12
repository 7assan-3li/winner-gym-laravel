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

    @include('reports.partials.header', [
        'title' => 'تقرير ملخص الإيرادات والعمليات المالي والتشغيلي',
        'subtitle' => 'ملخص شامل لكافة العمليات والإيرادات والمصروفات خلال الفترة المحددة',
        'meta' => [
            'من تاريخ' => $filters['from'],
            'إلى تاريخ' => $filters['to'],
            'الفئة' => $filters['gender'] === 'male' ? 'رجال فقط' : ($filters['gender'] === 'female' ? 'نساء فقط' : 'الكل'),
            'العملة' => $filters['currency'],
            'الموظف' => $user->name,
        ],
        'isPdf' => true,
    ])

    <table class="grid">
        <thead>
            <tr>
                <th style="width:65%;">المؤشر / البيان المالي والتشغيلي</th>
                <th style="width:35%;text-align:left;">القيمة والعملة</th>
            </tr>
        </thead>
        <tbody>
            @foreach([
                'إيراد الاشتراكات' => 'subscription_revenue',
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

    @include('reports.partials.footer', [
        'signEmployee' => $user->name,
        'signManager' => 'إدارة الصالة / المدير العام',
        'isPdf' => true,
    ])

</body>
</html>
