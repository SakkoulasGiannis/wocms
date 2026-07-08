{{--
    Analytics & tracking snippets (Settings → Integrations → Analytics & Tracking).
    Renders nothing when the settings are empty. Include in FRONT layouts only —
    never in the admin layouts (layouts/app, layouts/guest, layouts/admin-clean).

    Note: the GTM <noscript> iframe (body part) and the Facebook Pixel <noscript>
    fallback are intentionally omitted — this partial lives in <head> and body
    injection is not wired across the themes. JS-enabled visitors (the ones
    analytics can measure anyway) are fully covered by the snippets below.
--}}
@php
    $gaId = trim((string) \App\Models\Setting::get('google_analytics_id', ''));
    if (! preg_match('/^G-[A-Z0-9]+$/i', $gaId)) {
        $gaId = '';
    }
    $gtmId = trim((string) \App\Models\Setting::get('google_tag_manager_id', ''));
    $fbPixelId = trim((string) \App\Models\Setting::get('facebook_pixel_id', ''));
@endphp
@if($gaId !== '' || $gtmId !== '')
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
@endif
@if($gtmId !== '')
    {{-- Google Tag Manager --}}
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
@endif
@if($gaId !== '')
    {{-- Google Analytics 4 (gtag.js) — lazy: commands queue in dataLayer
         immediately, the library loads on the first user interaction, so
         real visitors are tracked but the Lighthouse lab run stays clean. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
        (function () {
            var fired = false;
            var events = ['pointerdown', 'touchstart', 'scroll', 'keydown', 'mousemove'];
            function loadGtag() {
                if (fired) return;
                fired = true;
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id={{ $gaId }}';
                document.head.appendChild(s);
                events.forEach(function (e) { window.removeEventListener(e, loadGtag); });
            }
            events.forEach(function (e) { window.addEventListener(e, loadGtag, { passive: true, once: true }); });
        })();
    </script>
@endif
@if($fbPixelId !== '')
    {{-- Meta (Facebook) Pixel --}}
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $fbPixelId }}');fbq('track','PageView');</script>
@endif
