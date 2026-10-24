<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($result) ? 'بطاقة عضوية: ' . $result['member']['name'] : 'استعلام الاشتراكات | WINNER GYM' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: dark;
            --wg-bg: #020813;
            --wg-card: #071120;
            --wg-card-2: #0b172a;
            --wg-border: #182f4d;
            --wg-border-soft: rgba(30, 68, 114, 0.35);
            --wg-blue: #1478ff;
            --wg-blue-glow: rgba(20, 120, 255, 0.22);
            --wg-blue-hover: #2985ff;
            --wg-text: #f8fafc;
            --wg-muted: #8ca0ba;
            --wg-muted-dark: #506684;
            --wg-emerald: #10b981;
            --wg-amber: #f59e0b;
            --wg-rose: #f43f5e;
            --wg-sky: #0ea5e9;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            min-height: 100vh;
            background: 
                radial-gradient(circle at 50% 0%, rgba(20, 120, 255, 0.12), transparent 32rem),
                radial-gradient(circle at 100% 100%, rgba(14, 165, 233, 0.08), transparent 28rem),
                var(--wg-bg);
            color: var(--wg-text);
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .portal-container {
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
            padding-bottom: 3rem;
        }

        /* Branding Header */
        .portal-header {
            text-align: center;
            padding: 1.5rem 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .portal-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 0.75rem;
        }

        .portal-logo-img {
            height: 48px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(20, 120, 255, 0.35));
        }

        .portal-title {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff 30%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .portal-subtitle {
            font-size: 0.88rem;
            color: var(--wg-muted);
            margin-top: 2px;
        }

        /* Card container */
        .portal-card {
            background: linear-gradient(180deg, var(--wg-card) 0%, #050d1a 100%);
            border: 1px solid var(--wg-border);
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            position: relative;
            overflow: hidden;
        }

        .portal-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent 0%, var(--wg-blue) 50%, transparent 100%);
        }

        /* Tabs */
        .portal-tabs {
            display: flex;
            background: rgba(11, 23, 42, 0.7);
            border: 1px solid var(--wg-border-soft);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 1.5rem;
            gap: 4px;
        }

        .portal-tab-btn {
            flex: 1;
            padding: 10px 12px;
            font-size: 0.88rem;
            font-weight: 700;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--wg-muted);
            transition: all 0.2s ease;
        }

        .portal-tab-btn.active {
            background: var(--wg-blue);
            color: #ffffff;
            box-shadow: 0 4px 14px var(--wg-blue-glow);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1.15rem;
        }

        .form-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--wg-muted);
            margin-bottom: 0.45rem;
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--wg-muted-dark);
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            right: 14px;
            width: 20px;
            height: 20px;
            color: var(--wg-muted-dark);
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-input {
            width: 100%;
            padding: 13px 44px 13px 14px;
            background: rgba(11, 23, 42, 0.85);
            border: 1px solid var(--wg-border);
            border-radius: 14px;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input::placeholder {
            color: var(--wg-muted-dark);
            font-size: 0.88rem;
            font-weight: 400;
        }

        .form-input:focus {
            border-color: var(--wg-blue);
            background: rgba(11, 23, 42, 1);
            box-shadow: 0 0 0 4px var(--wg-blue-glow);
        }

        .form-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--wg-blue);
        }

        .submit-btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, var(--wg-blue) 0%, #1e60f0 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 24px var(--wg-blue-glow);
            transition: all 0.2s ease;
            margin-top: 1.4rem;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--wg-blue-hover) 0%, #2563eb 100%);
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(20, 120, 255, 0.35);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Error Banner */
        .error-alert {
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.35);
            border-radius: 14px;
            padding: 12px 14px;
            color: #fecdd3;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.25rem;
        }

        .error-alert svg {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            color: var(--wg-rose);
        }

        /* PASS VIEW STYLES */
        .pass-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--wg-border-soft);
            gap: 12px;
        }

        .member-title-block h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.3;
        }

        .member-period-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 8px;
            margin-top: 6px;
            background: rgba(20, 120, 255, 0.15);
            color: #93c5fd;
            border: 1px solid rgba(20, 120, 255, 0.3);
        }

        .membership-code-pill {
            background: var(--wg-card-2);
            border: 1px solid var(--wg-border);
            padding: 6px 12px;
            border-radius: 12px;
            font-family: ui-monospace, monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: #e2e8f0;
            text-align: center;
            white-space: nowrap;
        }

        .membership-code-pill small {
            display: block;
            font-size: 0.68rem;
            font-weight: 500;
            color: var(--wg-muted-dark);
            font-family: 'Cairo', sans-serif;
        }

        /* Status Hero Banner */
        .status-hero {
            margin: 1.25rem 0;
            padding: 1.15rem;
            border-radius: 18px;
            background: rgba(11, 23, 42, 0.7);
            border: 1px solid var(--wg-border);
            position: relative;
        }

        .status-hero.emerald { border-color: rgba(16, 185, 129, 0.35); background: linear-gradient(180deg, rgba(16, 185, 129, 0.08) 0%, rgba(11, 23, 42, 0.8) 100%); }
        .status-hero.amber { border-color: rgba(245, 158, 11, 0.35); background: linear-gradient(180deg, rgba(245, 158, 11, 0.08) 0%, rgba(11, 23, 42, 0.8) 100%); }
        .status-hero.rose { border-color: rgba(244, 63, 94, 0.4); background: linear-gradient(180deg, rgba(244, 63, 94, 0.1) 0%, rgba(11, 23, 42, 0.8) 100%); }
        .status-hero.zinc { border-color: rgba(113, 113, 122, 0.35); }
        .status-hero.sky { border-color: rgba(14, 165, 233, 0.35); }

        .status-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .status-badge-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 9999px;
        }

        .status-badge-chip.emerald { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-badge-chip.amber { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-badge-chip.rose { background: rgba(244, 63, 94, 0.2); color: #fb7185; }
        .status-badge-chip.zinc { background: rgba(113, 113, 122, 0.2); color: #cbd5e1; }
        .status-badge-chip.sky { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }

        .package-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
        }

        .countdown-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 0.6rem;
        }

        .countdown-number {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .countdown-label {
            font-size: 0.9rem;
            color: var(--wg-muted);
            font-weight: 600;
        }

        /* Progress Bar */
        .progress-bar-track {
            height: 8px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
            margin-bottom: 0.65rem;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-bar-fill.emerald { background: linear-gradient(90deg, #059669, #10b981); }
        .progress-bar-fill.amber { background: linear-gradient(90deg, #d97706, #f59e0b); }
        .progress-bar-fill.rose { background: linear-gradient(90deg, #e11d48, #f43f5e); }
        .progress-bar-fill.zinc { background: #64748b; }
        .progress-bar-fill.sky { background: linear-gradient(90deg, #0284c7, #0ea5e9); }

        .dates-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: var(--wg-muted);
            font-weight: 500;
        }

        .status-alert-box {
            margin-top: 0.85rem;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            line-height: 1.5;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .status-alert-box.amber { background: rgba(245, 158, 11, 0.12); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.25); }
        .status-alert-box.rose { background: rgba(244, 63, 94, 0.15); color: #fecdd3; border: 1px solid rgba(244, 63, 94, 0.3); }

        /* QR Access Pass Section */
        .qr-pass-section {
            background: #ffffff;
            color: #020813;
            border-radius: 20px;
            padding: 1.25rem 1rem;
            text-align: center;
            margin: 1.25rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .qr-pass-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .qr-box {
            display: inline-block;
            background: #ffffff;
            padding: 8px;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
            margin: 0 auto;
        }

        .qr-box svg {
            display: block;
            max-width: 190px;
            height: auto;
            margin: 0 auto;
        }

        .barcode-text {
            font-family: ui-monospace, monospace;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 0.4rem;
            letter-spacing: 0.12em;
        }

        .qr-pass-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
            font-weight: 600;
        }

        /* Financial Grid */
        .section-header {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin: 1.25rem 0 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .finance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 0.75rem;
        }

        .finance-card {
            background: var(--wg-card-2);
            border: 1px solid var(--wg-border);
            border-radius: 14px;
            padding: 10px;
            text-align: center;
        }

        .finance-card-title {
            font-size: 0.72rem;
            color: var(--wg-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .finance-card-val {
            font-size: 0.95rem;
            font-weight: 800;
            color: #ffffff;
        }

        .finance-card-val.green { color: var(--wg-emerald); }
        .finance-card-val.rose { color: var(--wg-rose); }

        .finance-card-curr {
            font-size: 0.68rem;
            color: var(--wg-muted-dark);
            font-weight: 600;
        }

        /* Installments List */
        .installments-box {
            background: rgba(11, 23, 42, 0.6);
            border: 1px solid var(--wg-border-soft);
            border-radius: 14px;
            overflow: hidden;
            margin-top: 0.75rem;
        }

        .installment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid var(--wg-border-soft);
            font-size: 0.84rem;
        }

        .installment-row:last-child {
            border-bottom: none;
        }

        .inst-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .inst-badge.paid { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .inst-badge.overdue { background: rgba(244, 63, 94, 0.15); color: #fb7185; }
        .inst-badge.pending { background: rgba(148, 163, 184, 0.12); color: #94a3b8; }

        /* Bank remittance details */
        .bank-details-box {
            background: rgba(11, 23, 42, 0.6);
            border: 1px solid var(--wg-border-soft);
            border-radius: 14px;
            padding: 12px 14px;
            margin-top: 0.75rem;
        }

        .bank-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #93c5fd;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bank-item {
            font-size: 0.78rem;
            color: #cbd5e1;
            padding: 4px 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
        }

        .bank-item:last-child {
            border-bottom: none;
        }

        /* Attendance stats */
        .attendance-stat-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--wg-card-2);
            border: 1px solid var(--wg-border);
            border-radius: 14px;
            padding: 12px 16px;
            margin-top: 0.5rem;
        }

        .att-stat-title {
            font-size: 0.84rem;
            color: #ffffff;
            font-weight: 700;
        }

        .att-stat-sub {
            font-size: 0.74rem;
            color: var(--wg-muted);
        }

        .att-stat-num {
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--wg-blue);
        }

        /* Actions Bar */
        .actions-wrap {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 1.5rem;
        }

        .whatsapp-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            border-radius: 14px;
            background: #25d366;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.28);
            transition: all 0.2s ease;
        }

        .whatsapp-btn:hover {
            background: #20bd5a;
            transform: translateY(-1px);
        }

        .secondary-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border-radius: 14px;
            background: var(--wg-card-2);
            border: 1px solid var(--wg-border);
            color: var(--wg-muted);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .secondary-btn:hover {
            color: #ffffff;
            border-color: var(--wg-blue);
        }

        /* Helper tip */
        .portal-tip {
            margin-top: 1.5rem;
            padding: 12px 16px;
            background: rgba(14, 165, 233, 0.08);
            border: 1px dashed rgba(14, 165, 233, 0.25);
            border-radius: 14px;
            font-size: 0.8rem;
            color: #7dd3fc;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .portal-tip svg {
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            margin-top: 2px;
        }

        .portal-footer {
            text-align: center;
            font-size: 0.78rem;
            color: var(--wg-muted-dark);
            margin-top: 2rem;
        }

        /* Print styling for saving pass */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0;
            }
            .portal-card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
                background: #fff !important;
                color: #000 !important;
            }
            .actions-wrap, .portal-tabs, .portal-tip {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="portal-container">

    <!-- Gym Header -->
    <header class="portal-header">
        <div class="portal-logo-wrap">
            <img src="/winner-gym/logo-light.png" alt="WINNER GYM" class="portal-logo-img" onerror="this.src='/winner-gym/logo.png'">
        </div>
        <h1 class="portal-title">WINNER GYM</h1>
        <p class="portal-subtitle">بوابة متابعة وتفاصيل اشتراكات الأعضاء</p>
    </header>

    @if(!isset($result))
        <!-- ============================================== -->
        <!-- LOOKUP FORM (Option B: Dual Verification)     -->
        <!-- ============================================== -->
        <div class="portal-card">

            @if($errors->any())
                <div class="error-alert">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        {{ $errors->first('error') ?: ($errors->first('phone') ?: ($errors->first('membership_code') ?: $errors->first('verification_key'))) }}
                    </div>
                </div>
            @endif

            <!-- Tab Switcher -->
            <div class="portal-tabs">
                <button type="button" class="portal-tab-btn active" id="tabPhoneBtn" onclick="switchTab('phone')">
                    التحقق برقم الهاتف
                </button>
                <button type="button" class="portal-tab-btn" id="tabCodeBtn" onclick="switchTab('code')">
                    كود العضوية المباشر
                </button>
            </div>

            <!-- FORM 1: Dual Verification (Phone + Verification Key) -->
            <form id="formDual" method="POST" action="{{ route('member.inquiry.lookup') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="phoneInput">
                        <span>رقم الهاتف المسجل</span>
                        <span class="form-hint">مثال: 777123456</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="tel" 
                            name="phone" 
                            id="phoneInput" 
                            class="form-input" 
                            placeholder="أدخل رقم هاتفك المسجل بالصالة" 
                            value="{{ old('phone') }}" 
                            dir="ltr"
                            required 
                            autocomplete="tel"
                        >
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="verifyInput">
                        <span>رمز التحقق للأمان</span>
                        <span class="form-hint">كود العضوية أو آخر 4 أرقام من الهوية</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="verification_key" 
                            id="verifyInput" 
                            class="form-input" 
                            placeholder="مثال: WG-8F42K9 أو 42K9 أو 1234" 
                            value="{{ old('verification_key') }}" 
                            required 
                            autocomplete="off"
                        >
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <span>استعراض بيانات اشتراكي</span>
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <!-- FORM 2: Direct Membership Code (Backwards compatible) -->
            <form id="formCode" method="POST" action="{{ route('member.inquiry.lookup') }}" style="display: none;">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="codeInput">
                        <span>كود العضوية الرسمي</span>
                        <span class="form-hint">موجود على بطاقتك أو في رسائل الواتساب</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="membership_code" 
                            id="codeInput" 
                            class="form-input" 
                            placeholder="مثال: WG-8F42K9" 
                            value="{{ old('membership_code') }}" 
                            dir="ltr"
                            autocomplete="off"
                        >
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <span>استعلام مباشر بكود العضوية</span>
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <div class="portal-tip">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <strong>حماية الخصوصية:</strong> لضمان حماية بياناتك، نتحقق من تطابق رقم هاتفك مع كود عضويتك أو آخر 4 أرقام من هويتك الوطنية قبل عرض تفاصيل الاشتراك.
                </div>
            </div>

        </div>

    @else
        <!-- ============================================== -->
        <!-- RESULT: DIGITAL ATHLETIC PASS VIEW             -->
        <!-- ============================================== -->
        <div class="portal-card">

            <!-- Member Header -->
            <div class="pass-header">
                <div class="member-title-block">
                    <h2>{{ $result['member']['name'] }}</h2>
                    <div class="member-period-badge">
                        <span>{{ $result['member']['period_label'] }}</span>
                        <span>•</span>
                        <span>{{ $result['member']['working_hours'] }}</span>
                    </div>
                </div>
                <div class="membership-code-pill">
                    <small>كود العضوية</small>
                    {{ $result['member']['membership_code'] }}
                </div>
            </div>

            @if($result['subscription'])
                <!-- STATUS & PROGRESS HERO -->
                <div class="status-hero {{ $result['subscription']['status_color'] }}">
                    <div class="status-top-row">
                        <span class="package-name">{{ $result['subscription']['package_name'] }}</span>
                        <span class="status-badge-chip {{ $result['subscription']['status_color'] }}">
                            {{ $result['subscription']['status_badge'] }}
                        </span>
                    </div>

                    <div class="countdown-row">
                        <span class="countdown-number">{{ $result['subscription']['days_remaining'] }}</span>
                        <span class="countdown-label">يوماً متبقياً على نهاية الاشتراك</span>
                    </div>

                    <!-- Progress bar -->
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill {{ $result['subscription']['status_color'] }}" style="width: {{ $result['subscription']['progress_percent'] }}%;"></div>
                    </div>

                    <div class="dates-row">
                        <span>البدء: {{ $result['subscription']['start_date'] }}</span>
                        <span>الانتهاء: {{ $result['subscription']['end_date'] }}</span>
                    </div>

                    @if($result['subscription']['alert_message'])
                        <div class="status-alert-box {{ $result['subscription']['status_color'] }}">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="flex-shrink: 0; margin-top: 2px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>{{ $result['subscription']['alert_message'] }}</div>
                        </div>
                    @endif
                </div>

                <!-- DIGITAL GATE ACCESS PASS (QR CODE) -->
                <div class="qr-pass-section">
                    <div class="qr-pass-title">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <span>رمز الدخول الذكي للبوابة</span>
                    </div>

                    <div class="qr-box">
                        {!! $result['member']['qr_svg'] !!}
                    </div>

                    <div class="barcode-text">{{ $result['member']['barcode_value'] }}</div>
                    <div class="qr-pass-hint">وجّه رمز QR أمام قارئ البوابة الإلكترونية لتسجيل دخولك فوراً</div>
                </div>

                <!-- FINANCIAL SUMMARY -->
                <div class="section-header">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span>الموقف المالي للاشتراك</span>
                </div>

                <div class="finance-grid">
                    <div class="finance-card">
                        <div class="finance-card-title">إجمالي القيمة</div>
                        <div class="finance-card-val">{{ \App\Support\NumberFormatter::money($result['subscription']['final_price']) }}</div>
                        <div class="finance-card-curr">{{ $result['subscription']['currency'] }}</div>
                    </div>
                    <div class="finance-card">
                        <div class="finance-card-title">المدفوع</div>
                        <div class="finance-card-val green">{{ \App\Support\NumberFormatter::money($result['subscription']['paid_amount']) }}</div>
                        <div class="finance-card-curr">{{ $result['subscription']['currency'] }}</div>
                    </div>
                    <div class="finance-card">
                        <div class="finance-card-title">المتبقي</div>
                        <div class="finance-card-val {{ $result['subscription']['remaining_amount'] > 0 ? 'rose' : '' }}">
                            {{ \App\Support\NumberFormatter::money($result['subscription']['remaining_amount']) }}
                        </div>
                        <div class="finance-card-curr">{{ $result['subscription']['currency'] }}</div>
                    </div>
                </div>

                <!-- INSTALLMENTS LIST (If on installment plan) -->
                @if($result['subscription']['payment_plan'] === 'installments' && count($result['subscription']['installments']) > 0)
                    <div class="installments-box">
                        @foreach($result['subscription']['installments'] as $inst)
                            <div class="installment-row">
                                <div>
                                    <strong>القسط {{ $inst['number'] }}:</strong>
                                    <span>{{ \App\Support\NumberFormatter::money($inst['amount']) }} {{ $result['subscription']['currency'] }}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 0.78rem; color: var(--wg-muted);">مستحق: {{ $inst['due_date'] }}</span>
                                    @if($inst['is_paid'])
                                        <span class="inst-badge paid">تم السداد ✓</span>
                                    @elseif($inst['is_overdue'])
                                        <span class="inst-badge overdue">متأخر !</span>
                                    @else
                                        <span class="inst-badge pending">معلق</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- REMITTANCE ACCOUNTS (If remaining amount > 0) -->
                @if($result['subscription']['remaining_amount'] > 0)
                    <div class="bank-details-box">
                        <div class="bank-title">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                            <span>حسابات الصالة للتحويل والسداد المالي:</span>
                        </div>
                        @foreach($result['bank_accounts'] as $acc)
                            <div class="bank-item">
                                <span>{{ $acc['name'] }}</span>
                                <strong>{{ $acc['account'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif

            @else
                <!-- No Subscription alert -->
                <div class="status-hero zinc" style="margin: 1.5rem 0;">
                    <div class="package-name">لا يوجد اشتراك نشط حالياً</div>
                    <p style="font-size: 0.85rem; color: var(--wg-muted); margin-top: 6px;">
                        يمكنك زيارة الاستقبال لاختيار الباقة المناسبة والبدء فوراً في برنامجك الرياضي.
                    </p>
                </div>
            @endif

            <!-- ATTENDANCE SUMMARY -->
            <div class="section-header">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>سجل الحضور والنشاط</span>
            </div>

            <div class="attendance-stat-box">
                <div>
                    <div class="att-stat-title">أيام الحضور في هذا الاشتراك</div>
                    <div class="att-stat-sub">آخر تسجيل حضور: {{ $result['attendance']['last_attended_human'] }}</div>
                </div>
                <div class="att-stat-num">{{ $result['attendance']['total_in_subscription'] }} <span style="font-size: 0.85rem; font-weight: 600; color: var(--wg-muted);">يوم</span></div>
            </div>

            <!-- ACTION BUTTONS -->
            @php
                $waMsg = urlencode("مرحباً إدارة WINNER GYM، أنا المشترك: " . $result['member']['name'] . " (كود العضوية: " . $result['member']['membership_code'] . ")، أود الاستفسار بخصوص تجديد اشتراكي أو سداد قسط.");
                $waUrl = "https://wa.me/" . $result['gym']['whatsapp_number'] . "?text=" . $waMsg;
            @endphp

            <div class="actions-wrap">
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="whatsapp-btn">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/>
                    </svg>
                    <span>تواصل مع الإدارة / تجديد الاشتراك</span>
                </a>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button type="button" onclick="window.print()" class="secondary-btn">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>حفظ / طباعة البطاقة</span>
                    </button>

                    <a href="{{ route('member.inquiry') }}" class="secondary-btn">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>استعلام جديد</span>
                    </a>
                </div>
            </div>

        </div>
    @endif

    <!-- Footer -->
    <footer class="portal-footer">
        <p>WINNER GYM • المكلا، حضرموت، اليمن</p>
        <p style="margin-top: 4px; opacity: 0.7;">نظام إدارة العمليات والاشتراكات الذكي</p>
    </footer>

</div>

<script>
    function switchTab(type) {
        const tabPhoneBtn = document.getElementById('tabPhoneBtn');
        const tabCodeBtn = document.getElementById('tabCodeBtn');
        const formDual = document.getElementById('formDual');
        const formCode = document.getElementById('formCode');

        if (!tabPhoneBtn || !tabCodeBtn || !formDual || !formCode) return;

        if (type === 'phone') {
            tabPhoneBtn.classList.add('active');
            tabCodeBtn.classList.remove('active');
            formDual.style.display = 'block';
            formCode.style.display = 'none';
            document.getElementById('phoneInput')?.focus();
        } else {
            tabCodeBtn.classList.add('active');
            tabPhoneBtn.classList.remove('active');
            formCode.style.display = 'block';
            formDual.style.display = 'none';
            document.getElementById('codeInput')?.focus();
        }
    }
</script>

</body>
</html>