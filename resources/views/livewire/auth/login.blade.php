<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول | WINNER GYM</title>
    <style>
        :root {
            color-scheme: dark;
            --wg-bg: #020813;
            --wg-panel: #07111f;
            --wg-panel-2: #0a1525;
            --wg-line: #1b3556;
            --wg-line-soft: rgba(72, 115, 166, .28);
            --wg-blue: #1478ff;
            --wg-blue-2: #315df5;
            --wg-text: #f7faff;
            --wg-muted: #8493aa;
            --wg-placeholder: #68778f;
            --wg-danger: #ff5364;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 88% 8%, rgba(18, 89, 235, .11), transparent 28rem),
                #020813;
            color: var(--wg-text);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        button, input { font: inherit; }
        a { color: inherit; text-decoration: none; }

        .login-page {
            min-height: 100vh;
            padding: clamp(16px, 2.2vw, 30px);
            display: grid;
            place-items: center;
        }

        .login-shell {
            width: min(1560px, 100%);
            min-height: min(790px, calc(100vh - 36px));
            display: grid;
            /* Keep the visual layout fixed: artwork LEFT, Arabic form RIGHT. */
            direction: ltr;
            grid-template-columns: minmax(520px, .78fr) minmax(620px, 1.22fr);
            overflow: hidden;
            border: 1px solid rgba(25, 115, 255, .78);
            border-radius: 22px;
            background: #020a16;
            box-shadow: 0 28px 80px rgba(0, 0, 0, .42);
        }

        .hero-panel {
            position: relative;
            grid-column: 1;
            min-height: 760px;
            overflow: hidden;
            direction: ltr;
            isolation: isolate;
            background:
                radial-gradient(circle at 62% 42%, rgba(18, 100, 255, .14), transparent 31rem),
                linear-gradient(180deg, #020813 0%, #01060d 55%, #020813 100%);
            border-right: 1px solid rgba(35, 84, 145, .55);
            display: grid;
            place-items: center;
            padding: 0;
        }

        /* The artwork is deliberately blended into the panel so it reads as part of the UI,
           not as a separate pasted rectangle. */
        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(2,8,19,.76) 0%, transparent 7%, transparent 93%, rgba(2,8,19,.82) 100%),
                linear-gradient(180deg, rgba(2,8,19,.68) 0%, transparent 7%, transparent 91%, rgba(2,8,19,.82) 100%);
            box-shadow: inset 0 0 78px rgba(0, 0, 0, .26);
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 25%;
            z-index: 2;
            pointer-events: none;
            background: linear-gradient(180deg, transparent 0%, rgba(1, 6, 13, .18) 45%, rgba(1, 6, 13, .48) 100%);
        }

        .hero-image {
            position: relative;
            z-index: 1;
            display: block;
            width: 100%;
            height: 100%;
            min-height: 760px;
            object-fit: contain;
            object-position: center center;
            /* A tiny scale removes the artwork's own edge/border without cropping the logo. */
            transform: scale(1.012);
            transform-origin: center;
            filter: saturate(.98) contrast(1.015);
            user-select: none;
            -webkit-user-drag: none;
        }

        .form-panel {
            grid-column: 2;
            min-height: 760px;
            padding: clamp(34px, 5vw, 72px);
            display: grid;
            place-items: center;
            direction: rtl;
            background:
                radial-gradient(circle at 86% 10%, rgba(32, 98, 255, .10), transparent 26rem),
                linear-gradient(118deg, rgba(4, 15, 31, .99), rgba(7, 24, 59, .98));
        }

        .login-card {
            width: min(650px, 100%);
            border: 1px solid rgba(76, 112, 162, .44);
            border-radius: 26px;
            padding: clamp(34px, 4vw, 58px);
            background:
                linear-gradient(180deg, rgba(5, 14, 26, .98), rgba(6, 17, 31, .98));
            box-shadow:
                0 0 0 1px rgba(13, 37, 70, .55),
                0 24px 62px rgba(0, 0, 0, .34);
        }

        .login-heading { text-align: right; margin-bottom: 34px; }
        .login-heading h1 {
            margin: 0;
            font-size: clamp(30px, 2.2vw, 38px);
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -.3px;
        }
        .login-heading p {
            margin: 10px 0 0;
            color: #8ea0ba;
            font-size: 16px;
            line-height: 1.7;
        }

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
        }
        .alert-success { color: #75eeb1; background: rgba(18, 159, 96, .1); border: 1px solid rgba(34, 197, 94, .25); }
        .alert-error { color: #ff9aa5; background: rgba(255, 60, 80, .08); border: 1px solid rgba(255, 70, 90, .22); }

        .form-group { margin-bottom: 22px; }
        .field-label {
            display: block;
            margin-bottom: 10px;
            color: #f2f6fc;
            font-weight: 700;
            font-size: 16px;
        }

        .field-wrap { position: relative; }
        .field-icon {
            position: absolute;
            top: 50%;
            right: 16px;
            width: 22px;
            height: 22px;
            transform: translateY(-50%);
            color: #71829b;
            pointer-events: none;
        }
        .field-icon svg { width: 100%; height: 100%; display: block; }

        .login-input {
            width: 100%;
            height: 62px;
            padding: 0 52px 0 48px;
            border: 1px solid #3a4f6d;
            border-radius: 14px;
            background: #0a1424 !important;
            color: #f8fbff !important;
            caret-color: #fff;
            outline: none !important;
            box-shadow: none !important;
            transition: border-color .18s ease, background-color .18s ease;
            font-size: 16px;
        }
        .login-input::placeholder { color: var(--wg-placeholder); opacity: 1; }
        .login-input:hover { border-color: #506887; }
        .login-input:focus,
        .login-input:focus-visible {
            border-color: #1482ff !important;
            background: #0a1424 !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(20, 130, 255, .12) !important;
        }

        /* Chrome/Edge autofill: prevents the white autofill glow/background. */
        .login-input:-webkit-autofill,
        .login-input:-webkit-autofill:hover,
        .login-input:-webkit-autofill:focus,
        .login-input:-webkit-autofill:active {
            -webkit-text-fill-color: #f8fbff !important;
            caret-color: #fff !important;
            -webkit-box-shadow: 0 0 0 1000px #0a1424 inset !important;
            box-shadow: 0 0 0 1000px #0a1424 inset !important;
            border-color: #3a4f6d !important;
            transition: background-color 99999s ease-in-out 0s;
        }
        .login-input:-webkit-autofill:focus {
            border-color: #1482ff !important;
            -webkit-box-shadow: 0 0 0 1000px #0a1424 inset, 0 0 0 2px rgba(20, 130, 255, .12) !important;
            box-shadow: 0 0 0 1000px #0a1424 inset, 0 0 0 2px rgba(20, 130, 255, .12) !important;
        }

        .password-toggle {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            padding: 0;
            border: 0;
            background: transparent;
            color: #71829b;
            cursor: pointer;
            border-radius: 8px;
            box-shadow: none !important;
            outline: none !important;
        }
        .password-toggle:hover { color: #a9b7ca; background: rgba(255,255,255,.035); }
        .password-toggle svg { width: 21px; height: 21px; }

        .form-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: -2px 0 24px;
        }
        .remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #d8e0ec;
            font-size: 14px;
            cursor: pointer;
        }
        .remember input {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 1px solid #455a76;
            border-radius: 6px;
            background: #0a1424;
            display: grid;
            place-content: center;
            margin: 0;
            box-shadow: none !important;
            outline: none !important;
        }
        .remember input::before {
            content: "";
            width: 9px;
            height: 5px;
            border-inline-start: 2px solid white;
            border-bottom: 2px solid white;
            transform: rotate(-45deg) scale(0);
            transition: transform .12s ease;
            margin-top: -2px;
        }
        .remember input:checked { background: #167aff; border-color: #167aff; }
        .remember input:checked::before { transform: rotate(-45deg) scale(1); }
        .help-link { color: #1583ff; font-size: 14px; font-weight: 600; }
        .help-link:hover { color: #53a5ff; }

        .login-button {
            width: 100%;
            height: 66px;
            border: 0;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 13px;
            background: linear-gradient(100deg, #228dff, #2f5df1);
            color: #fff;
            font-size: 20px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(23, 103, 255, .18);
            transition: transform .14s ease, filter .14s ease;
        }
        .login-button:hover { filter: brightness(1.07); transform: translateY(-1px); }
        .login-button:active { transform: translateY(0); }
        .login-button:focus-visible { outline: 2px solid #70b4ff; outline-offset: 3px; }
        .login-button:disabled {
            cursor: wait;
            filter: saturate(.78);
            opacity: .86;
            transform: none;
        }
        .login-button svg { width: 22px; height: 22px; }

        .brand-divider {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-top: 36px;
            color: #267fff;
            font-size: 11px;
            letter-spacing: .45em;
            direction: ltr;
            white-space: nowrap;
        }
        .brand-divider::before,
        .brand-divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #34455e;
        }

        @media (max-width: 1180px) {
            .login-shell { grid-template-columns: minmax(440px, .82fr) minmax(520px, 1.18fr); }
            .form-panel { padding: 34px; }
            .login-card { padding: 38px; }
        }

        @media (max-width: 920px) {
            .login-page { padding: 14px; }
            .login-shell { display: block; min-height: auto; direction: rtl; }
            .hero-panel { display: none; }
            .form-panel { min-height: calc(100vh - 28px); padding: 26px 18px; }
            .login-card { width: min(620px, 100%); }
        }

        @media (max-width: 560px) {
            .login-card { padding: 28px 20px; border-radius: 20px; }
            .login-heading { margin-bottom: 28px; }
            .login-heading h1 { font-size: 28px; }
            .login-heading p { font-size: 14px; }
            .field-label { font-size: 14px; }
            .login-input { height: 56px; font-size: 15px; }
            .login-button { height: 58px; font-size: 18px; }
            .form-meta { align-items: flex-start; flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-shell" aria-label="تسجيل الدخول إلى WINNER GYM">
            <div class="hero-panel" aria-hidden="true">
                <img
                    class="hero-image"
                    data-src="{{ asset('winner-gym/login-hero-reference.webp') }}"
                    width="740"
                    height="972"
                    decoding="async"
                    alt=""
                >
                <noscript>
                    <img class="hero-image" src="{{ asset('winner-gym/login-hero-reference.webp') }}" width="740" height="972" alt="">
                </noscript>
            </div>

            <div class="form-panel">
                <div class="login-card">
                    <header class="login-heading">
                        <h1>مرحبًا بك</h1>
                        <p>سجّل الدخول للوصول إلى لوحة التحكم</p>
                    </header>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form id="login-form" method="POST" action="{{ route('login.store') }}" autocomplete="on">
                        @csrf

                        <div class="form-group">
                            <label class="field-label" for="username">اسم المستخدم</label>
                            <div class="field-wrap">
                                <span class="field-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input
                                    id="username"
                                    name="username"
                                    type="text"
                                    class="login-input"
                                    value="{{ old('username') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    placeholder="أدخل اسم المستخدم"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="field-label" for="password">كلمة المرور</label>
                            <div class="field-wrap">
                                <span class="field-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="login-input"
                                    required
                                    autocomplete="current-password"
                                    placeholder="أدخل كلمة المرور"
                                >
                                <button class="password-toggle" type="button" id="password-toggle" aria-label="إظهار كلمة المرور" title="إظهار كلمة المرور">
                                    <svg id="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                    <svg id="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.5 10.5 0 0 1 12 6c6 0 9.5 6 9.5 6a16.5 16.5 0 0 1-3 3.7M6.2 6.2C3.8 8 2.5 12 2.5 12s3.5 6 9.5 6a9.9 9.9 0 0 0 3.2-.5"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-meta">
                            <label class="remember">
                                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                <span>تذكرني</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a class="help-link" href="{{ route('password.request') }}">هل تحتاج مساعدة؟</a>
                            @endif
                        </div>

                        <button type="submit" class="login-button" data-test="login-button">
                            <span id="login-button-label">دخول</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg>
                        </button>

                        <div class="brand-divider">WINNER GYM</div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const heroImage = document.querySelector('.hero-image[data-src]');
            const desktopViewport = window.matchMedia('(min-width: 921px)');
            const loadDesktopArtwork = () => {
                if (desktopViewport.matches && heroImage && !heroImage.getAttribute('src')) {
                    heroImage.fetchPriority = 'high';
                    heroImage.src = heroImage.dataset.src;
                }
            };

            loadDesktopArtwork();
            desktopViewport.addEventListener?.('change', loadDesktopArtwork);

            const input = document.getElementById('password');
            const button = document.getElementById('password-toggle');
            const openIcon = document.getElementById('eye-open');
            const closedIcon = document.getElementById('eye-closed');
            if (input && button) {
                button.addEventListener('click', () => {
                    const showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    button.setAttribute('aria-label', showing ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
                    button.setAttribute('title', showing ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
                    openIcon.style.display = showing ? '' : 'none';
                    closedIcon.style.display = showing ? 'none' : '';
                    input.focus({ preventScroll: true });
                });
            }

            const form = document.getElementById('login-form');
            const submitButton = form?.querySelector('.login-button');
            const submitLabel = document.getElementById('login-button-label');
            const resetSubmitButton = () => {
                if (!submitButton || !submitLabel) return;
                submitButton.disabled = false;
                submitButton.removeAttribute('aria-busy');
                submitLabel.textContent = 'دخول';
            };

            form?.addEventListener('submit', () => {
                if (!form.checkValidity() || !submitButton || submitButton.disabled) return;
                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
                submitLabel.textContent = 'جاري الدخول...';
            });
            window.addEventListener('pageshow', event => {
                if (event.persisted) resetSubmitButton();
            });
        })();
    </script>
</body>
</html>
