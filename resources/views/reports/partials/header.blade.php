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
        if (! file_exists($fullPath)) {
            $fullPath = public_path('storage/' . $path);
        }
        if (! file_exists($fullPath)) {
            return null;
        }

        if ($isPdf) {
            $mime = @mime_content_type($fullPath) ?: 'image/png';
            $data = @file_get_contents($fullPath);
            return $data ? ('data:' . $mime . ';base64,' . base64_encode($data)) : null;
        }

        return asset('storage/' . $path);
    };

    $headerImgSrc = $resolveImg($settings['header_image'] ?? null);
    $logoSrc = $resolveImg($settings['logo'] ?? null);
@endphp

<div class="wg-report-header" style="width:100%;margin-bottom:14px;direction:rtl;">
    @if($headerMode === 'custom_image' && $headerImgSrc)
        {{-- النمط الثاني: صورة مروّسة كاملة جاهزة --}}
        <div style="width:100%;text-align:center;margin-bottom:10px;">
            <img src="{{ $headerImgSrc }}" alt="ترويسة التقرير" style="width:100%;max-height:160px;display:block;margin:0 auto;">
        </div>
    @else
        {{-- النمط الأول: كليشة بيانات وشعار منظمة باستخدام جدول HTML القياسي المتوافق مع المتصفح و mPDF --}}
        <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #1e293b;padding-bottom:10px;margin-bottom:8px;">
            <tr>
                {{-- الجانب الأيمن: بيانات المنشأة بالعربية --}}
                <td style="width:38%;vertical-align:middle;text-align:right;border:none;padding:4px 0;">
                    <div style="font-size:16px;font-weight:bold;color:#0f172a;line-height:1.2;">{{ $settings['gym_name'] }}</div>
                    @if(!empty($settings['tagline']))
                        <div style="font-size:10px;font-weight:bold;color:#475569;margin-top:2px;">{{ $settings['tagline'] }}</div>
                    @endif
                    @if(!empty($settings['commercial_register']))
                        <div style="font-size:9px;color:#64748b;margin-top:2px;">س.ت / ترخيص: {{ $settings['commercial_register'] }}</div>
                    @endif
                    @if(!empty($settings['location']))
                        <div style="font-size:9px;color:#64748b;margin-top:1px;">{{ $settings['location'] }}</div>
                    @endif
                </td>

                {{-- الوسط: شعار النادي --}}
                <td style="width:24%;vertical-align:middle;text-align:center;border:none;padding:4px 0;">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="شعار النادي" style="max-height:60px;max-width:120px;display:inline-block;">
                    @else
                        <div style="display:inline-block;padding:6px 12px;border:2px solid #0f172a;border-radius:6px;font-weight:bold;font-size:12px;color:#0f172a;background:#f8fafc;">
                            WINNER
                        </div>
                    @endif
                </td>

                {{-- الجانب الأيسر: بيانات المنشأة بالإنجليزية والتواصل --}}
                <td style="width:38%;vertical-align:middle;text-align:left;direction:ltr;border:none;padding:4px 0;">
                    <div style="font-size:14px;font-weight:bold;color:#0f172a;line-height:1.2;">{{ $settings['gym_name_en'] ?: 'WINNER GYM' }}</div>
                    <div style="font-size:9px;color:#475569;margin-top:2px;">SPORTS & FITNESS CLUB</div>
                    @if(!empty($settings['phone']))
                        <div style="font-size:9px;color:#64748b;margin-top:2px;">Tel: {{ $settings['phone'] }}</div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    {{-- عنوان التقرير وشريط البيانات الوصفية (إن وجد) --}}
    @if(!empty($title))
        <div style="margin-top:10px;text-align:center;">
            <h2 style="margin:0;font-size:15px;font-weight:bold;color:#0f172a;display:inline-block;padding-bottom:3px;border-bottom:2px solid #0284c7;">
                {{ $title }}
            </h2>
            @if(!empty($subtitle))
                <div style="font-size:10px;color:#475569;margin-top:4px;">{{ $subtitle }}</div>
            @endif
        </div>
    @endif

    @if(!empty($meta) && is_array($meta))
        <table style="width:100%;border-collapse:collapse;margin-top:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:10px;color:#334155;">
            <tr>
                @foreach($meta as $label => $val)
                    <td style="padding:5px 8px;border:none;white-space:nowrap;">
                        <span style="color:#64748b;font-weight:bold;">{{ $label }}:</span>
                        <strong style="color:#0f172a;margin-right:3px;">{{ $val }}</strong>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif
</div>
