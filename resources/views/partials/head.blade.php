<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

{{-- WINNER GYM Favicon Suite with Cache-Buster --}}
<link rel="icon" href="{{ asset('favicon.ico') }}?v=20261114" sizes="any">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20261114">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=20261114">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=20261114">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=20261114">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v=20261114">
<meta name="theme-color" content="#020813">
<meta name="apple-mobile-web-app-title" content="WINNER GYM">
<meta name="application-name" content="WINNER GYM">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
@fluxAppearance
