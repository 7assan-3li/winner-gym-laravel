<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>إيصال {{ $sale->sale_number }}</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#edf1f5;color:#111827;font-family:Tahoma,Arial,sans-serif}.receipt{width:min(82mm,calc(100% - 20px));margin:18px auto;background:#fff;padding:7mm 6mm;box-shadow:0 8px 35px #0f172a26}.brand{text-align:center;border-bottom:2px dashed #cbd5e1;padding-bottom:13px}.brand h1{font-size:20px;margin:0}.brand p{font-size:10px;color:#64748b;margin:5px 0 0}.meta{display:grid;gap:6px;padding:13px 0;border-bottom:1px dashed #cbd5e1;font-size:10px}.meta div,.totals div{display:flex;justify-content:space-between;gap:12px}.meta b{direction:ltr}.items{width:100%;border-collapse:collapse;margin-top:10px;font-size:9px}.items th{color:#64748b;border-bottom:1px solid #cbd5e1;padding:7px 2px}.items td{padding:8px 2px;border-bottom:1px solid #e2e8f0}.items th:first-child,.items td:first-child{text-align:right}.items th:not(:first-child),.items td:not(:first-child){text-align:center}.totals{display:grid;gap:7px;padding-top:12px;font-size:10px}.totals .grand{font-size:15px;font-weight:900;padding-top:9px;border-top:2px solid #111827}.status{text-align:center;margin-top:14px;padding:8px;border-radius:6px;background:#dcfce7;color:#166534;font-size:10px;font-weight:800}.status.cancelled{background:#fee2e2;color:#991b1b}.thanks{text-align:center;margin:14px 0 0;color:#64748b;font-size:9px}.actions{display:flex;gap:8px;justify-content:center;margin:14px auto}.actions button,.actions a{height:42px;padding:0 20px;border-radius:8px;font-weight:800;cursor:pointer}.actions button{border:0;background:#146ef5;color:#fff}.actions a{border:1px solid #cbd5e1;background:#fff;color:#334155;text-decoration:none;display:grid;place-items:center;font-size:12px}@media print{body{background:#fff}.receipt{width:80mm;margin:0;box-shadow:none;padding:4mm}.actions{display:none}@page{size:80mm auto;margin:0}}
</style>
</head>
<body>
@php $customer = $sale->member?->full_name ?: ($sale->customer_name ?: 'عميل نقدي'); @endphp
<main class="receipt">
<header class="brand"><h1>WINNER GYM</h1><p>نظام الإدارة والمالية المتكامل</p></header>
<section class="meta"><div><span>رقم الفاتورة</span><b>{{ $sale->sale_number }}</b></div><div><span>التاريخ</span><b>{{ optional($sale->sold_at)->timezone('Asia/Aden')->format('Y-m-d h:i A') }}</b></div><div><span>العميل</span><b>{{ $customer }}</b></div><div><span>الدفع</span><b>{{ $sale->payment_method === 'transfer' ? 'تحويل' : 'نقدي' }}</b></div></section>
<table class="items"><thead><tr><th>المنتج</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>
@foreach($sale->items as $item)<tr><td>{{ $item->product?->name ?? 'منتج محذوف' }}</td><td>{{ number_format($item->quantity) }}</td><td>{{ \App\Support\NumberFormatter::money($item->actual_unit_price) }}</td><td>{{ \App\Support\NumberFormatter::money($item->line_total) }}</td></tr>@endforeach
</tbody></table>
<section class="totals"><div><span>المجموع</span><b>{{ \App\Support\NumberFormatter::money($sale->subtotal) }} {{ $sale->currency }}</b></div>@if((float)$sale->discount_amount > 0)<div><span>الخصم</span><b>- {{ \App\Support\NumberFormatter::money($sale->discount_amount) }} {{ $sale->currency }}</b></div>@endif<div class="grand"><span>الإجمالي</span><b>{{ \App\Support\NumberFormatter::money($sale->total_amount) }} {{ $sale->currency }}</b></div></section>
<div class="status {{ $sale->status === 'completed' ? '' : 'cancelled' }}">{{ $sale->status === 'completed' ? 'فاتورة مكتملة' : 'فاتورة ملغاة' }}</div><p class="thanks">شكرًا لاختياركم WINNER GYM</p>
</main>
<div class="actions"><button type="button" onclick="window.print()">طباعة الإيصال</button><a href="{{ route('inventory.sales') }}">رجوع لنقطة البيع</a></div>
</body></html>