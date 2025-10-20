<!DOCTYPE html>
<html lang="ko" class="{{ (($appearance ?? 'system') == 'dark') ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="naver-site-verification" content="d38dc0cb3c08594d6f931db8566686d940c6c9e7" />
    <meta name="language" content="Korean" />

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
    <script type="text/javascript" src="//t1.daumcdn.net/kas/static/ba.min.js" async></script>

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

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="ko_KR" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:site_name" content="주보고" />
    <meta property="og:title" content="주보고 - 교회 주보와 소식" />
    <meta property="og:description" content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다. 하나님께 보고 드리는 시간 주보고가 올려 드립니다." />
    <meta property="og:image" content="{{ url('/og_image.png') }}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="{{ url('/') }}" />
    <meta name="twitter:title" content="주보고 - 교회 주보와 소식" />
    <meta name="twitter:description" content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다. 하나님께 보고 드리는 시간 주보고가 올려 드립니다." />
    <meta name="twitter:image" content="{{ url('/og_image.png') }}" />

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/jubogo_favicon.png">
    <link rel="icon" type="image/x-icon" href="/jubogo_favicon.ico">
    <link rel="shortcut icon" href="/jubogo_favicon.ico">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/site.webmanifest">

    <!-- Schema.org Organization -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "주보고",
        "legalName": "해시미터",
        "url": "{{ url('/') }}",
        "logo": "{{ url('/og_image.png') }}",
        "foundingDate": "2025",
        "contactPoint": {
            "@@type": "ContactPoint",
            "contactType": "customer service",
            "email": "ost5253@gmail.com"
        },
        "sameAs": []
    }
    </script>

    <!-- Schema.org WebSite -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "주보고",
        "alternateName": "교회 주보와 소식",
        "url": "{{ url('/') }}",
        "description": "교회의 모든 부서 주보와 소식을 한곳에 모았습니다. 하나님께 보고 드리는 시간 주보고가 올려 드립니다.",
        "inLanguage": "ko-KR",
        "publisher": {
            "@@type": "Organization",
            "name": "주보고"
        }
    }
    </script>

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
