<!DOCTYPE html>
<html lang="ko" class="{{ (($appearance ?? 'system') == 'dark') ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="naver-site-verification" content="d38dc0cb3c08594d6f931db8566686d940c6c9e7" />
    <meta name="naver-site-verification" content="412837f0e82d7f332b93f558db15607fbf8c9c40" />
    <meta name="language" content="Korean" />
    <meta name="author" content="주보고 - PCAview" />
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />

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

    <!-- Preconnect for performance optimization -->
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>
    <link rel="dns-prefetch" href="https://www.google-analytics.com">

    <!-- Google tag (gtag.js) -->
    @if(app()->isProduction())
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-4S522L2ZLN"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());

            gtag('config', 'G-4S522L2ZLN');
        </script>
    @endif

    <!-- Google AdSense -->
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
    @inertiaHead

    <!-- Favicon - Google recommends multiples of 48x48 pixels -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="48x48" href="/jubogo_favicon.png?v=2">
    <link rel="icon" type="image/png" sizes="96x96" href="/jubogo_favicon_96.png?v=2">
    <link rel="shortcut icon" href="/favicon.ico?v=2">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/site.webmanifest">

    <!-- Schema.org Organization -->
    <script type="application/ld+json">
        {
           "@@context": "https://schema.org",
           "@@type": "Organization",
           "name": "PCAview",
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
           "name": "PCAview",
           "alternateName": "피카뷰",
           "url": "{{ url('/') }}",
   "description": "트렌딩 뉴스와 실시간 소식을 한눈에. 다양한 분야의 최신 트렌드와 이슈를 빠르게 확인하세요.",
   "inLanguage": "ko-KR",
   "publisher": {
       "@@type": "Organization",
       "name": "PCAview"
   }
}
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Kakao JavaScript SDK -->
    <script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.2/kakao.min.js"
            crossorigin="anonymous"></script>

    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @routes
</head>
<body class="bg-white font-sans antialiased">
<div class="min-h-screen bg-white">
    @inertia
</div>
</body>
</html>
