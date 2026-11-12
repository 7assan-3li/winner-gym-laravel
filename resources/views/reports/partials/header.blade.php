@php
    $settings = $reportSettings ?? [
        'header_mode' => app(\App\Services\SettingsService::class)->get('report.header_mode', 'structured'),
        'header_image' => app(\App\Services\SettingsService::class)->get('report.header_image_path'),
        'logo' => app(\App\Services\SettingsService::class)->get('report.logo_path'),
        'gym_name' => app(\App\Services\SettingsService::class)->get('gym.name', 'WINNER GYM'),
        'gym_name_en' => app(\App\Services\SettingsService::class)->get('report.header_title_en', 'WINNER GYM'),
        'tagline' => app(\App\Services\SettingsService::class)->get('report.tagline', 'صالة اللياقة وبناء الأجسام'),
        'phone' => app(\App\Services\SettingsService::class)->get('gym.phone', ''),
        'location' => app(\App\Services\SettingsService::class)->get('gym.location', 'المكلا، اليمن'),
        'commercial_register' => app(\App\Services\SettingsService::class)->get('gym.commercial_register', ''),
    ];

    $isPdf = $isPdf ?? false;
    $headerMode = $settings['header_mode'] ?? 'structured';

    // Helper to resolve image source safely for web vs Mpdf
    $resolveImg = function(?string $path) use ($isPdf) {
        if (! $path) return null;
        $fullPath = storage_path('app/public/' . $path);
        if ($isPdf) {
            return file_exists($fullPath) ? $fullPath : null;
        }
        return asset('storage/' . $path);
    };

    $headerImgSrc = $resolveImg($settings['header_image'] ?? null);
    $logoSrc = $resolveImg($settings['logo'] ?? null);
@endphp

<header class="wg-report-header" style="width:100%;margin-bottom:14px;direction:rtl;font-family:inherit;">
    @if($headerMode === 'custom_image' && $headerImgSrc)
        {{-- النمط الثاني: صورة مروّسة كاملة جاهزة --}}
        <div class="wg-report-banner" style="width:100%;text-align:center;margin-bottom:10px;">
            <img src="{{ $headerImgSrc }}" alt="ترويسة التقرير" style="width:100%;max-height:160px;object-fit:contain;display:block;margin:0 auto;">
        </div>
    @else
        {{-- النمط الأول: كليشة بيانات وشعار منظمة --}}
        <div class="wg-report-structured" style="display:table;width:100%;border-bottom:2px solid #1e293b;padding-bottom:12px;margin-bottom:8px;">
            {{-- الجانب الأيمن: بيانات المنشأة بالعربية --}}
            <div style="display:table-cell;width:38%;vertical-align:middle;text-align:right;">
                <div style="font-size:17px;font-weight:900;color:#0f172a;line-height:1.2;">{{ $settings['gym_name'] }}</div>
                @if(!empty($settings['tagline']))
                    <div style="font-size:10px;font-weight:700;color:#475569;margin-top:2px;">{{ $settings['tagline'] }}</div>
                @endif
                @if(!empty($settings['commercial_register']))
                    <div style="font-size:9px;color:#64748b;margin-top:2px;">س.ت / ترخيص: {{ $settings['commercial_register'] }}</div>
                @endif
                @if(!empty($settings['location']))
                    <div style="font-size:9px;color:#64748b;margin-top:1px;">{{ $settings['location'] }}</div>
                @endif
            </div>

            {{-- الوسط: شعار النادي --}}
            <div style="display:table-cell;width:24%;vertical-align:middle;text-align:center;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="شعار النادي" style="max-height:65px;max-width:120px;object-fit:contain;display:inline-block;">
                @else
                    <div style="display:inline-block;padding:8px 14px;border:2px solid #0f172a;border-radius:8px;font-weight:900;font-size:13px;letter-spacing:1px;color:#0f172a;background:#f8fafc;">
                        WINNER
                    </div>
                @endif
            </div>

            {{-- الجانب الأيسر: بيانات المنشأة بالإنجليزية والتواصل --}}
            <div style="display:table-cell;width:38%;vertical-align:middle;text-align:left;direction:ltr;">
                <div style="font-size:15px;font-weight:900;color:#0f172a;letter-spacing:0.5px;line-height:1.2;">{{ $settings['gym_name_en'] ?: 'WINNER GYM' }}</div>
                <div style="font-size:9px;font-weight:600;color:#475569;margin-top:2px;">SPORTS & FITNESS CLUB</div>
                @if(!empty($settings['phone']))
                    <div style="font-size:9px;color:#64748b;margin-top:2px;">Tel: {{ $settings['phone'] }}</div>
                @endif
            </div>
        </div>
    @endif

    {{-- عنوان التقرير وشريط البيانات الوصفية (إن وجد) --}}
    @if(!empty($title))
        <div style="margin-top:10px;text-align:center;">
            <h2 style="margin:0;font-size:16px;font-weight:900;color:#0f172a;display:inline-block;padding-bottom:3px;border-bottom:2px solid #0284c7;">
                {{ $title }}
            </h2>
            @if(!empty($subtitle))
                <div style="font-size:11px;color:#475569;margin-top:4px;">{{ $subtitle }}</div>
            @endif
        </div>
    @endif

    @if(!empty($meta) && is_array($meta))
        <div style="display:table;width:100%;margin-top:10px;padding:6px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:10px;color:#334155;">
            <div style="display:table-row;">
                @foreach($meta as $label => $val)
                    <div style="display:table-cell;padding:2px 8px;white-space:nowrap;">
                        <span style="color:#64748b;font-weight:600;">{{ $label }}:</span>
                        <strong style="color:#0f172a;margin-right:3px;">{{ $val }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</header>
