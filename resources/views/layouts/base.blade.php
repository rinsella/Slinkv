@php
    $siteName = \App\Models\Setting::get('site_name', 'SlinkV');
    $siteTitle = \App\Models\Setting::get('site_title', 'SlinkV - URL Shortener dengan Bot Protection & Analytics Real-time');
    $metaDesc = \App\Models\Setting::get('meta_description', 'SlinkV adalah URL shortener profesional dengan analytics real-time, bot protection, dan device tracking.');
    $supportWa = \App\Models\Setting::get('support_whatsapp', '6281234567890');
@endphp
<!doctype html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $title ?? $siteTitle }}</title>
<meta name="description" content="{{ $description ?? $metaDesc }}">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:title" content="{{ $title ?? $siteTitle }}">
<meta property="og:description" content="{{ $description ?? $metaDesc }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url('/og-image.svg') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ url('/favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ url('/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ url('/site.webmanifest') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('head')
</head>
<body class="h-full bg-surface text-ink antialiased">
@yield('body')
@stack('scripts')
</body>
</html>
