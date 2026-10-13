<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#F3F5F4" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0B0F0F" media="(prefers-color-scheme: dark)">
        {{-- Apply the saved theme before first paint to avoid a flash. --}}
        <script nonce="{{ Vite::cspNonce() }}">
            try { var t = localStorage.getItem('eb-theme'); if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t; } catch (e) {}
            document.documentElement.classList.add('js');
        </script>
        {{-- Self-hosted fonts (resources/css/fonts.css): the first-view files download alongside the HTML. --}}
        @foreach (\App\Support\Fonts::firstView() as $font)
            <link rel="preload" href="{{ Vite::asset($font) }}" as="font" type="font/woff2" crossorigin>
        @endforeach
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="alternate" type="application/xml" href="{{ route('sitemap') }}">
        @if (Vite::isRunningHot())
            @vite('resources/css/app.css')
        @else
            {{-- The whole stylesheet is ~8 KB gzip: inlining it removes the only render-blocking request. --}}
            <style nonce="{{ Vite::cspNonce() }}">{!! Vite::content('resources/css/app.css') !!}</style>
        @endif
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
