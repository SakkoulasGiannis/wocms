{{-- Auto-refresh CSRF token to prevent 419 errors --}}
<script>
(function() {
    'use strict';

    // Refresh CSRF token every 60 minutes (before the 120 minute session expires)
    const REFRESH_INTERVAL = 60 * 60 * 1000; // 60 minutes in milliseconds

    function refreshCsrfToken() {
        fetch('{{ route("csrf-token") }}', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('CSRF token refresh failed');
            }
            return response.json();
        })
        .then(data => {
            if (data.csrf_token) {
                // Update all CSRF token inputs
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = data.csrf_token;
                });

                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.csrf_token);
                }

                // Update Livewire if present
                if (window.Livewire) {
                    window.Livewire.hook('request', ({ uri, options }) => {
                        options.headers = options.headers || {};
                        options.headers['X-CSRF-TOKEN'] = data.csrf_token;
                    });
                }

                console.log('✅ CSRF token refreshed successfully');
            }
        })
        .catch(error => {
            console.warn('⚠️ CSRF token refresh error:', error);
        });
    }

    // Refresh immediately on page load
    setTimeout(refreshCsrfToken, 5000); // Wait 5 seconds after page load

    // Then refresh every REFRESH_INTERVAL
    setInterval(refreshCsrfToken, REFRESH_INTERVAL);

    // Also refresh before user submits a form (if the page has been idle)
    let lastRefresh = Date.now();

    document.addEventListener('submit', function(e) {
        const timeSinceLastRefresh = Date.now() - lastRefresh;

        // If more than 30 minutes since last refresh, refresh now
        if (timeSinceLastRefresh > 30 * 60 * 1000) {
            e.preventDefault();
            refreshCsrfToken();
            lastRefresh = Date.now();

            // Re-submit after 1 second
            setTimeout(() => {
                e.target.submit();
            }, 1000);
        }
    }, true);

    // Update last refresh time when token is refreshed
    window.addEventListener('csrf-token-refreshed', () => {
        lastRefresh = Date.now();
    });
})();
</script>
