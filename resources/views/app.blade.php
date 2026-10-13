<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#F3F5F4" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0B0F0F" media="(prefers-color-scheme: dark)">
        {{-- Apply the saved theme before first paint to avoid a flash. --}}
        <script>
            try { var t = localStorage.getItem('eb-theme'); if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t; } catch (e) {}
            document.documentElement.classList.add('js');
        </script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,300..900&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Noto+Sans+Ethiopic:wght@400;600&display=swap">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="alternate" type="application/xml" href="{{ route('sitemap') }}">
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
