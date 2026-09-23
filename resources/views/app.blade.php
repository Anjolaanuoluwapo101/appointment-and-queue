<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
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
