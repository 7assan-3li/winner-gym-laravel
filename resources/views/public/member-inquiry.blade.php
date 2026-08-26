<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>استعلام العضوية - WINNER GYM</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900"><main class="mx-auto max-w-xl p-6 md:pt-20"><div class="rounded-3xl bg-white p-6 shadow-sm">
<h1 class="text-2xl font-bold">WINNER GYM</h1><p class="mt-1 text-sm text-zinc-500">استعلام حالة العضوية</p>
<form method="POST" action="{{ route('member.inquiry.lookup') }}" class="mt-6 space-y-3">@csrf<input name="membership_code" value="{{ old('membership_code') }}" class="w-full rounded-xl border px-4 py-3" placeholder="مثال: WG-8F42K9" autocomplete="off"><button class="w-full rounded-xl bg-zinc-900 px-4 py-3 text-white">استعلام</button></form>
@error('membership_code')<div class="mt-3 text-sm text-red-600">{{ $message }}</div>@enderror
@if(isset($result))<div class="mt-6 space-y-3 border-t pt-5 text-sm">
<div class="flex justify-between"><span>الاسم</span><b>{{ $result['name'] }}</b></div><div class="flex justify-between"><span>كود العضوية</span><b>{{ $result['membership_code'] }}</b></div>
<div class="flex justify-between"><span>حالة الاشتراك</span><b>{{ $result['subscription_status'] }}</b></div><div class="flex justify-between"><span>الباقة</span><b>{{ $result['package'] }}</b></div>
<div class="flex justify-between"><span>البداية</span><b>{{ $result['start_date'] ?? '-' }}</b></div><div class="flex justify-between"><span>النهاية</span><b>{{ $result['end_date'] ?? '-' }}</b></div>
<div class="flex justify-between"><span>الأيام المتبقية</span><b>{{ $result['days_remaining'] }}</b></div><div class="flex justify-between"><span>المدفوع</span><b>{{ \App\Support\NumberFormatter::money($result['paid']) }} {{ $result['currency'] }}</b></div>
<div class="flex justify-between"><span>المتبقي</span><b>{{ \App\Support\NumberFormatter::money($result['remaining']) }} {{ $result['currency'] }}</b></div>
</div>@endif
</div></main></body></html>