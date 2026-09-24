<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Hospital Queue System') }}</title>
    <meta name="description" content="Book hospital appointments, track your live queue ticket, and clear the desk — patient and staff portals for modern outpatient flow.">
    <meta name="theme-color" content="#18181b">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'Hospital Queue System') }}">
    <meta property="og:title" content="{{ config('app.name', 'Hospital Queue System') }}">
    <meta property="og:description" content="Book appointments, track your live queue ticket, and clear the desk.">
    <meta property="og:image" content="/icon-512x512.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ config('app.name', 'Hospital Queue System') }}">
    <meta name="twitter:description" content="Book appointments, track your live queue ticket, and clear the desk.">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
    {{-- Runtime public config: lets the same built image point Echo at
         whichever host serves it (Render, local, VPS) without rebuilding. --}}
    @php
        $appConfig = [
            'reverbHost' => config('reverb.public.host'),
            'reverbPort' => config('reverb.public.port'),
            'reverbScheme' => config('reverb.public.scheme'),
            'reverbKey' => config('reverb.apps.apps.0.key'),
        ];
    @endphp
    <script>
        window.__APP_CONFIG__ = @json($appConfig);
    </script>
</head>
<body>
    @inertia
</body>
</html>
