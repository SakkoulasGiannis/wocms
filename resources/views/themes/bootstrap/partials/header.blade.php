<!-- Bootstrap Header -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm sticky-top">
    <div class="container">
        @php
            $siteName = \App\Models\Setting::get('site_name', 'CMS');
            $siteLogo = \App\Models\Setting::get('site_logo', '');
        @endphp

        <a class="navbar-brand d-flex align-items-center" href="/">
            @if($siteLogo)
                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" height="32" class="me-2">
            @endif
            <span class="fw-bold text-primary">{{ $siteName }}</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/blog">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/services">Services</a>
                </li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Admin</a>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="/login">Login</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
