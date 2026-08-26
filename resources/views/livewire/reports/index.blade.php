<div dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
<div><h1 class="text-2xl font-bold">التقارير</h1><p class="text-sm text-zinc-500">كل عملة تعرض منفصلة ولا يتم جمع YER مع SAR.</p></div>@if (session('success'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800"><ul class="list-disc pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid gap-3 rounded-2xl border bg-white p-4 md:grid-cols-4 dark:bg-zinc-900">
<label>من<input wire:model.live="from" type="date" class="mt-1 w-full rounded-xl border px-3 py-2"></label>
<label>إلى<input wire:model.live="to" type="date" class="mt-1 w-full rounded-xl border px-3 py-2"></label>
<label>الجنس<select wire:model.live="gender" class="mt-1 w-full rounded-xl border px-3 py-2"><option value="all">الكل</option><option value="male">رجال</option><option value="female">نساء</option></select></label>
<label>العملة<select wire:model.live="currency" class="mt-1 w-full rounded-xl border px-3 py-2"><option value="YER">YER</option><option value="SAR">SAR</option></select></label>
</div>
<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
@foreach([
'إيراد الاشتراكات'=>$data['subscription_revenue'],'إيراد التغذية'=>$data['nutrition_revenue'],
'إيراد المنتجات'=>$data['product_revenue'],'تكلفة البضاعة'=>$data['product_cogs'],
'ربح المنتجات'=>$data['product_profit'],'المصروفات'=>$data['expenses'],'الصافي'=>$data['net']
] as $label=>$value)
<div class="rounded-2xl border bg-white p-4 dark:bg-zinc-900"><div class="text-sm text-zinc-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold">{{ \App\Support\NumberFormatter::money($value) }} {{ $currency }}</div></div>
@endforeach
</div>
<div class="grid gap-3 md:grid-cols-3">
<div class="rounded-2xl border p-4">الحضور: <b>{{ $data['attendance_count'] }}</b></div>
<div class="rounded-2xl border p-4">المواعيد: <b>{{ $data['appointments_count'] }}</b></div>
<div class="rounded-2xl border p-4">الأعضاء الجدد: <b>{{ $data['new_members_count'] }}</b></div>
</div>
<a target="_blank" href="{{ route('reports.pdf', ['from'=>$from,'to'=>$to,'gender'=>$gender,'currency'=>$currency]) }}" class="inline-flex rounded-xl bg-zinc-900 px-4 py-2 text-white">تصدير PDF</a>
</div>