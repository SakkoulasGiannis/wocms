{{--
    Analytics & tracking snippets (Settings → Integrations → Analytics & Tracking).
    Renders nothing when the settings are empty. Include in FRONT layouts only —
    never in the admin layouts (layouts/app, layouts/guest, layouts/admin-clean).

    GDPR: the trackers below are DEFINED here but do NOT fire until the visitor
    grants consent. `window.enableTracking()` is invoked by the cookie-consent
    manager (partials/cookie-consent) on "Accept", or automatically on load when
    consent was granted on a previous visit. Nothing tracking-related — no GA,
    GTM, Meta Pixel, no cookies — runs before that. Google Search Console
    verification (a <meta> tag) sets no cookies and is unaffected.

    Note: the GTM <noscript> iframe and the Meta Pixel <noscript> fallback are
    intentionally omitted (this partial lives in <head>); JS-enabled visitors —
    the only ones analytics can measure — are fully covered.
--}}
@php
    $gaId = trim((string) \App\Models\Setting::get('google_analytics_id', ''));
    if (! preg_match('/^G-[A-Z0-9]+$/i', $gaId)) {
        $gaId = '';
    }
    $gtmId = trim((string) \App\Models\Setting::get('google_tag_manager_id', ''));
    $fbPixelId = trim((string) \App\Models\Setting::get('facebook_pixel_id', ''));
@endphp
@if($gaId !== '' || $gtmId !== '' || $fbPixelId !== '')
    @if($gaId !== '' || $gtmId !== '')
        <link rel="dns-prefetch" href="//www.googletagmanager.com">
    @endif
    <script>
        window.enableTracking = window.enableTracking || function () {
            if (window.__trackingOn) { return; }
            window.__trackingOn = true;
            @if($gtmId !== '')
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');
            @endif
            @if($gaId !== '')
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function(){ dataLayer.push(arguments); };
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id={{ $gaId }}';
            document.head.appendChild(s);
            @endif
            @if($fbPixelId !== '')
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $fbPixelId }}');fbq('track','PageView');
            @endif
        };
    </script>
@endif
