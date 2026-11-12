<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عقد اشتراك متدرب — {{ $member->full_name }} ({{ $member->membership_code }})</title>
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
            font-size: 13.5pt;
            line-height: 1.8;
            direction: rtl;
            text-align: justify;
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
            margin: 24px auto 48px auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Each A4 Page Sheet */
        .contract-page {
            width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            padding: 18mm 16mm;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* Outer Double Border matching user document */
        .contract-border {
            border: 4px double #000000;
            padding: 16mm 14mm;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .contract-head {
            text-align: center;
            margin-bottom: 22px;
        }

        .contract-bismillah {
            font-size: 15pt;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .contract-title {
            font-size: 16pt;
            font-weight: 900;
            text-decoration: underline;
            text-underline-offset: 6px;
            margin-bottom: 14px;
        }

        .section-bullet {
            font-size: 14pt;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .section-subtitle {
            font-size: 13.5pt;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .lead-text {
            margin-bottom: 16px;
            line-height: 1.9;
        }

        .party-block {
            margin-bottom: 14px;
            padding-right: 4px;
        }

        .party-title {
            font-size: 13.5pt;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .party-row {
            margin-bottom: 5px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px;
        }

        .party-row strong {
            font-weight: 800;
        }

        .data-value {
            font-weight: 700;
            color: #000;
            display: inline-block;
            border-bottom: 1px dotted #000;
            padding: 0 4px;
        }

        .dotted-line {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px dotted #000;
        }

        .preamble-box {
            margin-top: 18px;
            border-top: 1px dashed #94a3b8;
            padding-top: 14px;
            line-height: 1.9;
        }

        .clause-item {
            margin-bottom: 20px;
        }

        .clause-title {
            font-size: 14pt;
            font-weight: 900;
            margin-bottom: 8px;
            color: #000;
        }

        .clause-text {
            line-height: 1.9;
            margin-bottom: 6px;
            padding-right: 8px;
        }

        .clause-text strong {
            font-weight: 800;
        }

        .signatures-area {
            margin-top: auto;
            padding-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 13.5pt;
            font-weight: 800;
        }

        .signature-col {
            text-align: center;
            width: 45%;
        }

        .signature-dots {
            margin-top: 45px;
            border-bottom: 1px dashed #000;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .badge-code {
            display: inline-block;
            direction: ltr;
            font-family: monospace;
            font-size: 12pt;
            font-weight: 800;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .contract-wrapper {
                margin: 0 !important;
                max-width: none !important;
                gap: 0 !important;
            }

            .contract-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10mm !important;
                width: 100% !important;
                height: 100vh !important;
                page-break-after: always !important;
                page-break-inside: avoid !important;
            }

            .contract-page:last-child {
                page-break-after: avoid !important;
            }

            .contract-border {
                border: 3.5px double #000000 !important;
                padding: 12mm 10mm !important;
                height: 100% !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
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
                <header class="contract-head">
                    <div class="contract-bismillah">بسم الله الرحمن الرحيم</div>
                    <div class="contract-title">(عقد اشتراك المتدرب)</div>
                </header>

                <div class="section-bullet">• المقدمة</div>
                <div class="section-subtitle">عقد عمل / تقديم خدمات تدريبية</div>

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
                        <span>بصفته:</span>
                        <span class="data-value">مدير المركز</span>
                    </div>
                    <div class="party-row">
                        <span>* العنوان:</span>
                        <span class="data-value">{{ $gymLocation }}</span>
                        <span style="margin-right: 18px;">هاتف:</span>
                        <span class="data-value" dir="ltr">{{ $gymPhone }}</span>
                    </div>
                </div>

                {{-- Second Party --}}
                <div class="party-block">
                    <div class="party-title">* الطرف الثاني:</div>
                    <div class="party-row">
                        <span>* الاسم الثلاثي/الرباعي:</span>
                        <span class="data-value" style="font-size: 14pt;">{{ $member->full_name }}</span>
                    </div>
                    <div class="party-row">
                        <span>* الصفة:</span>
                        <span class="data-value">متدرب / مشترك</span>
                        <span style="margin-right: 18px;">كود العضوية:</span>
                        <span class="data-value badge-code">{{ $member->membership_code }}</span>
                    </div>
                    <div class="party-row">
                        <span>* رقم الهوية (البطاقة الشخصية / الجواز):</span>
                        <span class="data-value">{{ $member->identity_number ?: '........................................' }}</span>
                        <span style="margin-right: 18px;">صادرة من:</span>
                        <span class="dotted-line"></span>
                    </div>
                    <div class="party-row">
                        <span>* العنوان السكني:</span>
                        <span class="data-value">{{ $member->address ?: '........................................................................' }}</span>
                        <span style="margin-right: 18px;">هاتف:</span>
                        <span class="data-value" dir="ltr">{{ $member->phone }}</span>
                    </div>
                </div>

                {{-- Preamble --}}
                <div class="preamble-box">
                    <div style="font-weight: 800; margin-bottom: 6px;">تمهيد العقد</div>
                    <div>
                        حيث إن الطرف الأول يمارس نشاطه وفقاً للأنظمة واللوائح النافذة في الجمهورية اليمنية، بحيث تم الاتفاق بين المنشأة/المركز (طرف أول) والمتدرب/ة: <strong class="data-value">{{ $member->full_name }}</strong> (طرف ثاني) على التحاق الطرف الثاني بالتدريب وفقاً للشروط والبنود التالية:
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== PAGE 2 ==================== --}}
        <div class="contract-page">
            <div class="contract-border">
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
                        <div style="font-size: 11pt; color: #475569; margin-top: 4px;">{{ $gymName }}</div>
                        <div class="signature-dots"></div>
                    </div>
                    <div class="signature-col">
                        <div>الطرف الثاني /</div>
                        <div style="font-size: 11pt; color: #475569; margin-top: 4px;">المتدرب: {{ $member->full_name }}</div>
                        <div class="signature-dots"></div>
                    </div>
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
