<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="naver-site-verification" content="d38dc0cb3c08594d6f931db8566686d940c6c9e7" />

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HVL5EFDXYN"></script>
    @if(app()->isProduction())
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());

            gtag('config', 'G-HVL5EFDXYN');
        </script>
    @endif
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8665007420370986"
            crossorigin="anonymous"></script>
    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <meta name="description" content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다. 하나님께 보고 드리는 시간 주보고가 올려 드립니다.">

    <meta property="og:image" content="/og_image.png?v={{ rand(100000, 999999) }}" />
    <meta property="og:title" content="<?php echo e(config('app.name', 'Laravel')); ?>" />
    <meta property="og:description"
          content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다. 하나님께 보고 드리는 시간 주보고가 올려 드립니다." />

    <link rel="icon" href="/jubogo_favicon.png" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @routes
    @inertiaHead
</head>
<body class="font-sans antialiased body-bg2">
<div class="page-wrapper">
    @inertia
</div>
</body>
</html>
