<!-- Bootstrap Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                @php
                    $siteName = \App\Models\Setting::get('site_name', 'CMS');
                    $siteLogo = \App\Models\Setting::get('site_logo', '');
                    $siteDescription = \App\Models\Setting::get('site_description', 'Modern content management system built with Laravel');
                @endphp

                @if($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" height="48" class="mb-3">
                @endif

                <h5 class="fw-bold">{{ $siteName }}</h5>
                <p class="text-muted">{{ $siteDescription }}</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/" class="text-light text-decoration-none">Home</a></li>
                    @auth
                        <li class="mb-2"><a href="/admin" class="text-light text-decoration-none">Admin Panel</a></li>
                    @else
                        <li class="mb-2"><a href="/login" class="text-light text-decoration-none">Login</a></li>
                    @endauth
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">About</h5>
                <p class="text-muted">A flexible and modern CMS solution</p>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="row">
            <div class="col-12 text-center text-muted">
                <p class="mb-2">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'CMS') }}. All rights reserved.</p>
                <p class="small">Built by <a href="https://weborange.gr" target="_blank" class="text-primary">WebOrange.gr</a></p>
            </div>
        </div>
    </div>
</footer>
