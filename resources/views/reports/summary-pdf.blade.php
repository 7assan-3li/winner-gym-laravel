<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><style>
body{font-family:dejavusans;direction:rtl;font-size:12px;color:#222}h1{font-size:22px}.meta{margin-bottom:15px}.grid{width:100%;border-collapse:collapse}.grid td,.grid th{border:1px solid #ddd;padding:8px}.title{background:#f3f3f3;font-weight:bold}.num{text-align:left;direction:ltr}
</style></head><body>
<h1>WINNER GYM</h1><div class="meta">تقرير من {{ $filters['from'] }} إلى {{ $filters['to'] }} — العملة: {{ $filters['currency'] }} — التصدير: {{ now('Asia/Aden')->format('Y-m-d H:i') }} — الموظف: {{ $user->name }}</div>
<table class="grid"><tr class="title"><th>المؤشر</th><th>القيمة</th></tr>
@foreach(['إيراد الاشتراكات'=>'subscription_revenue','إيراد التغذية'=>'nutrition_revenue','إيراد المنتجات'=>'product_revenue','تكلفة البضاعة'=>'product_cogs','ربح المنتجات'=>'product_profit','المصروفات'=>'expenses','الصافي'=>'net'] as $label=>$key)
<tr><td>{{ $label }}</td><td class="num">{{ \App\Support\NumberFormatter::money($data[$key]) }} {{ $data['currency'] }}</td></tr>@endforeach
<tr><td>الحضور</td><td>{{ $data['attendance_count'] }}</td></tr><tr><td>المواعيد</td><td>{{ $data['appointments_count'] }}</td></tr><tr><td>الأعضاء الجدد</td><td>{{ $data['new_members_count'] }}</td></tr>
</table></body></html>