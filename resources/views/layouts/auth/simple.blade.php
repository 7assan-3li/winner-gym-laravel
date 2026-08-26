<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    @include('partials.head')
    <style>
        html,body{margin:0;min-height:100%;background:#020812;color:#fff}
        body{min-height:100vh;overflow:auto}
        .wgl-page{min-height:100vh;display:grid;place-items:center;padding:22px;box-sizing:border-box;direction:ltr;background:radial-gradient(circle at 89% 20%,rgba(16,79,214,.18),transparent 31rem),linear-gradient(180deg,#020812 0%,#030914 100%)}
        .wgl-frame{width:min(1492px,calc(100vw - 44px));height:min(976px,calc(100vh - 44px));min-height:650px;display:grid;grid-template-columns:1fr 1fr;overflow:hidden;border:1px solid #174899;border-radius:20px;background:#030914;box-shadow:0 30px 80px rgba(0,0,0,.30)}
        .wgl-visual{min-width:0;position:relative;overflow:hidden;border-right:1px solid rgba(18,41,77,.65);background:#020711}
        .wgl-visual img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;display:block;user-select:none;pointer-events:none}
        .wgl-panel{min-width:0;display:grid;place-items:center;padding:clamp(28px,4vw,68px) clamp(28px,3vw,48px);box-sizing:border-box;direction:rtl;background:radial-gradient(circle at 100% 0%,rgba(19,57,177,.34),transparent 43%),radial-gradient(circle at 72% 82%,rgba(1,37,102,.22),transparent 35%),linear-gradient(135deg,#030914 0%,#061126 56%,#07123a 100%)}
        .wgl-card{width:min(612px,100%);height:min(826px,calc(100vh - 120px));min-height:560px;display:flex;align-items:stretch;border:3px solid rgba(27,38,61,.80);border-radius:27px;background:radial-gradient(circle at 42% 43%,rgba(21,65,149,.08),transparent 42%),linear-gradient(145deg,rgba(6,14,28,.98),rgba(6,14,28,.96));box-shadow:0 25px 65px rgba(0,0,0,.30),inset 0 1px 0 rgba(255,255,255,.015);box-sizing:border-box}
        .wgl-inner{width:100%;min-height:100%;display:flex;flex-direction:column;padding:clamp(44px,7vh,96px) 56px 38px;box-sizing:border-box}
        .wgl-heading{text-align:center}.wgl-heading h1{margin:0;color:#f7f8fb;font-size:clamp(28px,2vw,34px);font-weight:800;line-height:1.35}.wgl-heading p{margin:15px 0 0;color:#747e91;font-size:16px}
        .wgl-errors,.wgl-status{margin:18px 0 -6px;padding:9px 12px;border-radius:9px;font-size:12px;text-align:right}.wgl-errors{color:#ff7b88;border:1px solid rgba(255,75,91,.27);background:rgba(65,12,20,.42)}.wgl-status{color:#34df94;border:1px solid rgba(52,223,148,.25);background:rgba(7,48,34,.45)}
        .wgl-form{display:grid;gap:30px;margin-top:clamp(34px,6vh,59px)}.wgl-group{display:grid;gap:12px}.wgl-group label{color:#f4f5f8;font-size:17px;line-height:1;font-weight:700;text-align:right}
        .wgl-control{height:68px;display:flex;align-items:center;gap:14px;position:relative;padding:0 18px;border:1px solid #46556f;border-radius:12px;background:rgba(12,21,37,.86);color:#6e7b91;box-sizing:border-box;transition:border-color .15s ease,box-shadow .15s ease}.wgl-control:focus-within{border-color:#0878ff;box-shadow:0 0 0 1px rgba(8,120,255,.78),0 -8px 26px -19px rgba(15,124,255,.9)}
        .wgl-control svg{width:27px!important;height:27px!important;min-width:27px!important;max-width:27px!important;min-height:27px!important;max-height:27px!important;display:block!important;flex:0 0 27px!important;color:#6c7890!important}
        .wgl-control input{width:100%!important;min-width:0!important;height:100%!important;border:0!important;outline:0!important;padding:0!important;margin:0!important;color:#f1f4fa!important;background:transparent!important;font-size:16px!important;text-align:right!important;box-shadow:none!important}.wgl-control input::placeholder{color:#68758b!important;opacity:.82}
        .wgl-eye{width:34px!important;height:34px!important;min-width:34px!important;display:grid!important;place-items:center!important;flex:0 0 34px!important;padding:0!important;border:0!important;color:#6f7d95!important;background:transparent!important;cursor:pointer!important}.wgl-eye svg{width:26px!important;height:26px!important;min-width:26px!important;max-width:26px!important}
        .wgl-options{min-height:28px;display:flex;align-items:center;justify-content:space-between;margin-top:-4px}.wgl-help{padding:0!important;border:0!important;color:#0c79ff!important;background:transparent!important;font-size:15px!important;cursor:pointer!important}.wgl-remember{display:inline-flex;align-items:center;gap:11px;color:#e2e5eb;font-size:15px;cursor:pointer}.wgl-remember input{appearance:none;width:21px!important;height:21px!important;min-width:21px!important;margin:0!important;border:1px solid #42506a!important;border-radius:5px!important;background:#09111f!important}.wgl-remember input:checked{border-color:#1378ff!important;background:#1378ff!important}
        .wgl-submit{height:77px!important;width:100%!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:18px!important;margin-top:-7px!important;border:1px solid #1681ff!important;border-radius:12px!important;color:#fff!important;background:linear-gradient(95deg,#1181ff 0%,#2256e8 100%)!important;box-shadow:inset 0 1px 0 rgba(255,255,255,.14),0 10px 25px rgba(7,66,195,.14)!important;font-size:20px!important;font-weight:700!important;cursor:pointer!important}.wgl-submit svg{width:26px!important;height:26px!important;min-width:26px!important;max-width:26px!important}
        .wgl-footer{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:15px;margin-top:auto;padding-top:30px}.wgl-footer span{height:1px;background:#202b3e}.wgl-footer strong{color:#147aff;font-size:12px;font-weight:500;letter-spacing:5px;direction:ltr;white-space:nowrap}
        @media(max-height:820px) and (min-width:981px){.wgl-page{padding:10px}.wgl-frame{height:calc(100vh - 20px);min-height:620px}.wgl-card{height:calc(100vh - 70px);min-height:540px}.wgl-inner{padding-top:42px;padding-bottom:24px}.wgl-form{margin-top:30px;gap:24px}.wgl-control{height:56px}.wgl-submit{height:60px!important}.wgl-footer{padding-top:18px}}
        @media(max-width:980px){.wgl-page{padding:0}.wgl-frame{width:100%;height:auto;min-height:100vh;grid-template-columns:1fr;border:0;border-radius:0}.wgl-visual{display:none}.wgl-panel{min-height:100vh;padding:26px 18px}.wgl-card{width:min(612px,100%);height:auto;min-height:auto}.wgl-inner{padding:54px 30px 30px}}
        @media(max-width:560px){.wgl-card{border-width:1px;border-radius:20px}.wgl-inner{padding:42px 20px 24px}.wgl-heading h1{font-size:28px}.wgl-heading p{font-size:14px}.wgl-form{margin-top:38px;gap:25px}.wgl-group label{font-size:15px}.wgl-control{height:58px}.wgl-submit{height:62px!important;font-size:18px!important}.wgl-options{font-size:13px}}
    </style>
</head>
<body>
<main class="wgl-page">
    <div class="wgl-frame">
        <section class="wgl-visual" aria-hidden="true">
            <img src="{{ asset('winner-gym/login-hero-reference.png') }}" alt="">
        </section>
        <section class="wgl-panel">
            <div class="wgl-card">{{ $slot }}</div>
        </section>
    </div>
</main>
@fluxScripts
</body>
</html>
