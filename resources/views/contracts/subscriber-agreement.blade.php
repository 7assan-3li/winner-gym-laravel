<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عقد اشتراك متدرب — {{ $member->full_name }} ({{ $member->membership_code }})</title>
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
            margin: 0;
            padding: 0;
        }

        body {
            background: #e2e8f0;
            color: #111827;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            direction: rtl;
            text-align: justify;
            -webkit-font-smoothing: antialiased;
        }

        .no-print-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #0f172a;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            font-family: 'Cairo', Tahoma, sans-serif;
        }

        .no-print-bar .title {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .no-print-bar .actions {
            display: flex;
            gap: 10px;
        }

        .btn-print, .btn-close {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 0;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-close {
            background: #334155;
            color: #f1f5f9;
        }

        .btn-close:hover {
            background: #475569;
        }

        /* Container for the paper sheets */
        .contract-wrapper {
            max-width: 210mm;
            margin: 20px auto 40px auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Each A4 Page Sheet on screen */
        .contract-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            background: #ffffff;
            padding: 10mm 12mm;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Outer Double Border matching user document */
        .contract-border {
            border: 3.5px double #000000;
            padding: 8mm 10mm;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Header Table */
        .contract-header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .contract-header-table td {
            vertical-align: middle;
            padding: 0 4px;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .header-gym-name {
            font-size: 13pt;
            font-weight: 900;
            color: #000;
            line-height: 1.3;
        }

        .header-gym-sub {
            font-size: 9.5pt;
            font-weight: 700;
            color: #334155;
            margin-top: 2px;
        }

        .header-gym-contact {
            font-size: 8.5pt;
            font-weight: 600;
            color: #475569;
            margin-top: 2px;
        }

        .header-center {
            width: 20%;
            text-align: center;
        }

        .contract-logo {
            max-height: 52px;
            max-width: 95px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .header-left {
            width: 40%;
            text-align: left;
            direction: ltr;
        }

        .header-bismillah {
            font-size: 11pt;
            font-weight: 800;
            color: #000;
            direction: rtl;
            text-align: left;
            margin-bottom: 4px;
        }

        .header-meta-row {
            font-size: 9pt;
            color: #1e293b;
            direction: rtl;
            text-align: left;
            line-height: 1.4;
        }

        .header-meta-row strong {
            font-weight: 800;
            color: #000;
        }

        /* Title */
        .contract-title-wrap {
            text-align: center;
            margin: 8px 0 12px 0;
        }

        .contract-title {
            display: inline-block;
            font-size: 15pt;
            font-weight: 900;
            color: #000;
            border-bottom: 2px solid #000;
            padding-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .section-bullet {
            font-size: 11.5pt;
            font-weight: 800;
            color: #000;
            margin-bottom: 4px;
        }

        .section-subtitle {
            font-size: 10.5pt;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }

        .lead-text {
            margin-bottom: 12px;
            line-height: 1.7;
            font-size: 10.5pt;
        }

        .party-block {
            margin-bottom: 10px;
            padding-right: 4px;
        }

        .party-title {
            font-size: 11pt;
            font-weight: 800;
            margin-bottom: 4px;
            color: #000;
        }

        .party-row {
            margin-bottom: 3px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px;
            font-size: 10.5pt;
        }

        .party-row strong {
            font-weight: 800;
        }

        .data-value {
            font-weight: 800;
            color: #000;
            display: inline-block;
            border-bottom: 1px dotted #000;
            padding: 0 4px;
        }

        .dotted-line {
            display: inline-block;
            min-width: 120px;
            border-bottom: 1px dotted #000;
        }

        .preamble-box {
            margin-top: 10px;
            border-top: 1px dashed #64748b;
            padding-top: 10px;
            line-height: 1.7;
            font-size: 10.5pt;
        }

        /* Page 2 Running Header */
        .contract-page2-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #000;
            padding-bottom: 6px;
            margin-bottom: 14px;
            font-size: 9pt;
            color: #334155;
        }

        .page2-header-right {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 800;
            color: #000;
        }

        .page2-mini-logo {
            max-height: 24px;
            max-width: 48px;
            object-fit: contain;
        }

        .page2-header-center {
            font-weight: 800;
            color: #000;
            font-size: 10pt;
        }

        .clause-item {
            margin-bottom: 12px;
        }

        .clause-title {
            font-size: 11pt;
            font-weight: 900;
            margin-bottom: 4px;
            color: #000;
        }

        .clause-text {
            line-height: 1.65;
            margin-bottom: 4px;
            padding-right: 6px;
            font-size: 10.5pt;
        }

        .clause-text strong {
            font-weight: 800;
        }

        .signatures-area {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed #64748b;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 11pt;
            font-weight: 800;
        }

        .signature-col {
            text-align: center;
            width: 46%;
        }

        .signature-dots {
            margin-top: 40px;
            border-bottom: 1px dashed #000;
            width: 85%;
            margin-left: auto;
            margin-right: auto;
        }

        .page-footer-counter {
            text-align: center;
            font-size: 9pt;
            color: #64748b;
            margin-top: 8px;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }

        .badge-code {
            display: inline-block;
            direction: ltr;
            font-family: monospace;
            font-size: 10.5pt;
            font-weight: 800;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                width: 210mm !important;
                height: auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .contract-wrapper {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                max-width: 210mm !important;
                gap: 0 !important;
            }

            .contract-page {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                padding: 8mm 10mm !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
                background: #ffffff !important;
                overflow: hidden !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .contract-page:last-child,
            .contract-page:last-of-type {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .contract-border {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                box-sizing: border-box !important;
                border: 3.5px double #000000 !important;
                padding: 7mm 9mm !important;
                width: 100% !important;
                height: 100% !important;
                overflow: hidden !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Only Navigation & Controls --}}
    <div class="no-print-bar">
        <div class="title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>عقد اشتراك متدرب — {{ $member->full_name }}</span>
        </div>
        <div class="actions">
            <button type="button" class="btn-print" onclick="window.print()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                <span>طباعة العقد (A4)</span>
            </button>
            <button type="button" class="btn-close" onclick="window.close()">إغلاق النافذة</button>
        </div>
    </div>

    <div class="contract-wrapper">

        {{-- ==================== PAGE 1 ==================== --}}
        <div class="contract-page">
            <div class="contract-border">
                <div class="page-content-top">
                    {{-- Formal Branded Header --}}
                    <header class="contract-head">
                        <table class="contract-header-table">
                            <tr>
                                <td class="header-right">
                                    <div class="header-gym-name">{{ $gymName }}</div>
                                    <div class="header-gym-sub">صالة وينر الرياضية للياقة وبناء الأجسام</div>
                                    <div class="header-gym-contact">
                                        <span>{{ $gymLocation }}</span>
                                        @if($gymPhone && $gymPhone !== '—')
                                            <span> • هاتف: {{ $gymPhone }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="header-center">
                                    <img src="{{ $gymLogo }}" alt="شعار النادي" class="contract-logo" onerror="this.src='{{ asset('winner-gym/logo-light.png') }}'">
                                </td>
                                <td class="header-left">
                                    <div class="header-bismillah">بسم الله الرحمن الرحيم</div>
                                    <div class="header-meta-row">كود العضوية: <strong class="badge-code">{{ $member->membership_code }}</strong></div>
                                    <div class="header-meta-row">تاريخ العقد: <strong>{{ $dayNumber }}/{{ $monthNumber }}/{{ $yearNumber }}م</strong></div>
                                </td>
                            </tr>
                        </table>
                        <div class="contract-title-wrap">
                            <h1 class="contract-title">(عـقــد اشـتــراك مـتــدرب)</h1>
                        </div>
                    </header>

                    <div class="section-bullet">• المقدمة:</div>
                    <div class="section-subtitle">عقد عمل / تقديم خدمات تدريبية رياضية</div>

                    <div class="lead-text">
                        إنه في يوم: <span class="data-value">{{ $dayName }}</span> الموافق: <span class="data-value">{{ $dayNumber }} / {{ $monthNumber }} / {{ $yearNumber }} م</span>، بمدينة: <span class="data-value">{{ $gymLocation }}</span> - الجمهورية اليمنية، تم الاتفاق بالتراضي والايجاب والقبول بين كلٍّ من:
                    </div>

                    {{-- First Party --}}
                    <div class="party-block">
                        <div class="party-title">* الطرف الأول (صاحب العمل / المركز):</div>
                        <div class="party-row">
                            <span>* الاسم / الجهة:</span>
                            <span class="data-value">{{ $gymName }}</span>
                        </div>
                        <div class="party-row">
                            <span>* رقم التسجيل / السجل التجاري:</span>
                            <span class="data-value">{{ $commercialRegister !== '—' ? $commercialRegister : '.....................................................' }}</span>
                        </div>
                        <div class="party-row">
                            <span>* يمثله في هذا العقد:</span>
                            <span class="data-value">{{ $managerName }}</span>
                            <span style="margin-right: 8px;">بصفته:</span>
                            <span class="data-value">مدير المركز</span>
                        </div>
                        <div class="party-row">
                            <span>* العنوان:</span>
                            <span class="data-value">{{ $gymLocation }}</span>
                            <span style="margin-right: 14px;">هاتف:</span>
                            <span class="data-value" dir="ltr">{{ $gymPhone }}</span>
                        </div>
                    </div>

                    {{-- Second Party --}}
                    <div class="party-block">
                        <div class="party-title">* الطرف الثاني:</div>
                        <div class="party-row">
                            <span>* الاسم الثلاثي/الرباعي:</span>
                            <span class="data-value" style="font-size: 11.5pt;">{{ $member->full_name }}</span>
                        </div>
                        <div class="party-row">
                            <span>* الصفة:</span>
                            <span class="data-value">متدرب / مشترك</span>
                            <span style="margin-right: 14px;">كود العضوية:</span>
                            <span class="data-value badge-code">{{ $member->membership_code }}</span>
                        </div>
                        <div class="party-row">
                            <span>* رقم الهوية (البطاقة الشخصية / الجواز):</span>
                            <span class="data-value">{{ $member->identity_number ?: '........................................' }}</span>
                            <span style="margin-right: 14px;">صادرة من:</span>
                            <span class="dotted-line"></span>
                        </div>
                        <div class="party-row">
                            <span>* العنوان السكني:</span>
                            <span class="data-value">{{ $member->address ?: '........................................................................' }}</span>
                            <span style="margin-right: 14px;">هاتف:</span>
                            <span class="data-value" dir="ltr">{{ $member->phone }}</span>
                        </div>
                    </div>

                    {{-- Preamble --}}
                    <div class="preamble-box">
                        <div style="font-weight: 800; margin-bottom: 4px; color: #000;">تمهيد العقد:</div>
                        <div>
                            حيث إن الطرف الأول يمارس نشاطه وفقاً للأنظمة واللوائح النافذة في الجمهورية اليمنية، بحيث تم الاتفاق بين المنشأة/المركز (طرف أول) والمتدرب/ة: <strong class="data-value">{{ $member->full_name }}</strong> (طرف ثاني) على التحاق الطرف الثاني بالتدريب وفقاً للشروط والبنود التالية:
                        </div>
                    </div>
                </div>

                {{-- Page 1 Footer Counter --}}
                <div class="page-footer-counter">
                    <span>(صفحة 1 من 2) — عقد اشتراك متدرب: {{ $member->full_name }}</span>
                </div>
            </div>
        </div>

        {{-- ==================== PAGE 2 ==================== --}}
        <div class="contract-page">
            <div class="contract-border">
                <div class="page-content-top">
                    {{-- Page 2 Running Header --}}
                    <header class="contract-page2-head">
                        <div class="page2-header-right">
                            <img src="{{ $gymLogo }}" alt="شعار النادي" class="page2-mini-logo" onerror="this.src='{{ asset('winner-gym/logo-light.png') }}'">
                            <span>{{ $gymName }}</span>
                        </div>
                        <div class="page2-header-center">تابع: عقد اشتراك المتدرب ({{ $member->full_name }})</div>
                        <div class="page2-header-left">كود المشترك: <span class="badge-code">{{ $member->membership_code }}</span></div>
                    </header>

                    {{-- Clause 1 --}}
                    <div class="clause-item">
                        <div class="clause-title">1- البند الأول: نظام التدريب والساعات</div>
                        <div class="clause-text">
                            <strong>نظام الاشتراك:</strong> يُعتبر هذا الاشتراك <strong>{{ $durationText }}</strong> ويُجدد بنهاية كل <strong>{{ $durationUnitText }}</strong>.
                        </div>
                        <div class="clause-text">
                            <strong>ساعات التدريب:</strong> يلتزم المتدرب بالدورة/الجلسات التدريبية بمعدل ساعتين (2) في اليوم وفق الجدول المحدد من قِبل إدارة المركز والمدرب (الفترة المخصصة: <strong>{{ $periodLabel }}</strong> — سريان الاشتراك من تاريخ: <strong>{{ $subscription->start_date->format('Y/m/d') }}</strong> إلى: <strong>{{ $subscription->end_date->format('Y/m/d') }}</strong>).
                        </div>
                    </div>

                    {{-- Clause 2 --}}
                    <div class="clause-item">
                        <div class="clause-title">2- البند الثاني: الرسوم والباقة المختارة</div>
                        <div class="clause-text">
                            <strong>رسوم الاشتراك:</strong> يدفع المتدرب مبلغ الاشتراك المحدد بحسب الباقة المختارة (باقة: <strong>{{ $subscription->package_name_snapshot }}</strong>) قبل بدء التدريب، بمبلغ إجمالي قدره: <strong>{{ \App\Support\NumberFormatter::money($subscription->final_price) }} {{ $subscription->currency }}</strong>.
                        </div>
                        <div class="clause-text">
                            <strong>سياسة استرداد الرسوم:</strong> الرسوم مدفوعة مقابل الباقة المحددة ولا تُسترد بعد بدء التدريب إلا في الحالات التي تقرها إدارة المركز.
                        </div>
                    </div>

                    {{-- Clause 3 --}}
                    <div class="clause-item">
                        <div class="clause-title">3- البند الثالث: التزامات المتدرب</div>
                        <div class="clause-text">
                            <strong>الحضور والانتظام:</strong> يلتزم المتدرب بالحضور في المواعيد المحددة لساعتَي التدريب، ولا يُعوض المركز أي ساعات يتم تفويتها من قِبل المتدرب دون عذر مقبول.
                        </div>
                        <div class="clause-text">
                            <strong>التعليمات العامة:</strong> يلتزم المتدرب بالتعليمات الداخلية للمركز والاطلاع على التحديثات الخاصة بالدورة.
                        </div>
                    </div>

                    {{-- Clause 4 --}}
                    <div class="clause-item">
                        <div class="clause-title">4- البند الرابع: الأحكام العامة</div>
                        <div class="clause-text">
                            - تسري أحكام القوانين النافذة واللوائح الداخلية المنظمة في الجمهورية اليمنية فيما لم يرد به نص في هذا العقد.
                        </div>
                    </div>

                    {{-- Signatures Area --}}
                    <div class="signatures-area">
                        <div class="signature-col">
                            <div>الطرف الأول عنهم /</div>
                            <div style="font-size: 10pt; color: #475569; margin-top: 3px;">{{ $gymName }} (الإدارة)</div>
                            <div class="signature-dots"></div>
                            <div style="font-size: 9pt; color: #64748b; margin-top: 6px;">التوقيع والختم</div>
                        </div>
                        <div class="signature-col">
                            <div>الطرف الثاني /</div>
                            <div style="font-size: 10pt; color: #475569; margin-top: 3px;">المتدرب: {{ $member->full_name }}</div>
                            <div class="signature-dots"></div>
                            <div style="font-size: 9pt; color: #64748b; margin-top: 6px;">توقيع المتدرب بالموافقة والإقرار</div>
                        </div>
                    </div>
                </div>

                {{-- Page 2 Footer Counter --}}
                <div class="page-footer-counter">
                    <span>(صفحة 2 من 2) — عقد اشتراك متدرب: {{ $member->full_name }}</span>
                </div>
            </div>
        </div>

    </div>

    @if($autoprint)
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
    @endif

</body>
</html>
