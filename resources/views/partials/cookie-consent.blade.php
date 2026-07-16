{{--
    GDPR cookie-consent banner + manager. Include ONCE per FRONT layout, just
    before </body>. Shows only when a tracker is configured (GA4 / GTM / Meta
    Pixel in Settings → Integrations). Blocks all tracking until the visitor
    clicks "Αποδοχή": on accept it calls window.enableTracking() (defined in
    partials/analytics) and stores the choice for a year. A "Ρυθμίσεις cookies"
    link anywhere can re-open it via window.openCookieSettings().
--}}
@php
    $ccGaId = trim((string) \App\Models\Setting::get('google_analytics_id', ''));
    if (! preg_match('/^G-[A-Z0-9]+$/i', $ccGaId)) {
        $ccGaId = '';
    }
    $ccGtmId = trim((string) \App\Models\Setting::get('google_tag_manager_id', ''));
    $ccFbId = trim((string) \App\Models\Setting::get('facebook_pixel_id', ''));
    $ccHasTrackers = $ccGaId !== '' || $ccGtmId !== '' || $ccFbId !== '';
    $ccPrivacyUrl = trim((string) \App\Models\Setting::get('privacy_policy_url', '')) ?: '/privacy-policy';
@endphp
@if($ccHasTrackers)
    <div id="cookie-consent" role="dialog" aria-live="polite" aria-label="Ρυθμίσεις cookies" hidden
         style="position:fixed;left:0;right:0;bottom:0;z-index:2147483000;background:#111827;color:#f9fafb;padding:14px 16px;box-shadow:0 -4px 24px rgba(0,0,0,.25);font-size:14px;line-height:1.5">
        <div style="max-width:1100px;margin:0 auto;display:flex;flex-wrap:wrap;gap:12px 16px;align-items:center;justify-content:space-between">
            <div style="flex:1 1 300px;min-width:260px">
                Χρησιμοποιούμε cookies για ανάλυση της επισκεψιμότητας (Google Analytics). Δεν φορτώνουν πριν τη συγκατάθεσή σου.
                <a href="{{ $ccPrivacyUrl }}" style="color:#93c5fd;text-decoration:underline">Πολιτική Απορρήτου</a>.
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                <button type="button" data-cc-reject
                        style="cursor:pointer;border:1px solid #4b5563;background:transparent;color:#e5e7eb;padding:9px 16px;border-radius:8px;font-weight:600">Απόρριψη</button>
                <button type="button" data-cc-accept
                        style="cursor:pointer;border:0;background:#f97316;color:#fff;padding:9px 18px;border-radius:8px;font-weight:700">Αποδοχή</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            function getConsent() {
                var m = document.cookie.match(/(?:^|; )cookie_consent=([^;]+)/);
                return m ? decodeURIComponent(m[1]) : null;
            }
            function setConsent(v) {
                var d = new Date();
                d.setFullYear(d.getFullYear() + 1);
                document.cookie = 'cookie_consent=' + v + '; path=/; expires=' + d.toUTCString() + '; SameSite=Lax';
            }
            var banner = document.getElementById('cookie-consent');
            function show() { if (banner) { banner.hidden = false; } }
            function hide() { if (banner) { banner.hidden = true; } }
            function enable() { if (typeof window.enableTracking === 'function') { window.enableTracking(); } }
            function accept() { setConsent('granted'); enable(); hide(); }
            function reject() { setConsent('denied'); hide(); }

            var c = getConsent();
            if (c === 'granted') { enable(); }
            else if (c !== 'denied') { show(); }

            if (banner) {
                var a = banner.querySelector('[data-cc-accept]'); if (a) { a.addEventListener('click', accept); }
                var r = banner.querySelector('[data-cc-reject]'); if (r) { r.addEventListener('click', reject); }
            }
            window.openCookieSettings = function () { show(); };
        })();
    </script>
@endif
