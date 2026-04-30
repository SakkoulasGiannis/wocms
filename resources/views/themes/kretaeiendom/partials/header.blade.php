<style>
    .main-header .main-menu .navigation > li { padding-right: 18px; position: relative; }
    .main-header .main-menu .navigation > li:last-child { padding-right: 0; }
    .main-header .main-menu .navigation > li > a { color: #0f507e; font-size: 15px; line-height: 19px; padding: 27px 10px; }
    .main-header .main-menu .navigation > li.dropdown2 > a::after { font-size: 14px; right: -16px; }

    /* ── Submenu hover effect (homelengo-style smooth slide+fade) ─────────────── */
    .main-header .main-menu .navigation > li > ul,
    .main-header .main-menu .navigation > li > ul.sub-menu {
        opacity: 0;
        visibility: hidden;
        transform: translateY(12px);
        pointer-events: none;
        transition: opacity .25s ease, transform .25s ease, visibility .25s ease;
        z-index: 100;
    }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul,
    .main-header .main-menu .navigation > li.dropdown2:hover > ul.sub-menu,
    .main-header .main-menu .navigation > li.dropdown2:focus-within > ul,
    .main-header .main-menu .navigation > li.dropdown2:focus-within > ul.sub-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        pointer-events: auto;
    }

    /* Second-level submenu (slides in from the right of the parent item) */
    .main-header .main-menu .navigation > li > ul > li > ul,
    .main-header .main-menu .navigation > li > ul > li > ul.sub-menu {
        opacity: 0;
        visibility: hidden;
        transform: translateX(8px);
        pointer-events: none;
        transition: opacity .25s ease, transform .25s ease, visibility .25s ease;
    }
    .main-header .main-menu .navigation > li > ul > li.dropdown2:hover > ul,
    .main-header .main-menu .navigation > li > ul > li.dropdown2:hover > ul.sub-menu,
    .main-header .main-menu .navigation > li > ul > li.dropdown2:focus-within > ul,
    .main-header .main-menu .navigation > li > ul > li.dropdown2:focus-within > ul.sub-menu {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
        pointer-events: auto;
    }

    /* Item-level fade-in stagger (each row slides up slightly when parent opens) */
    .main-header .main-menu .navigation > li > ul > li {
        opacity: 0;
        transform: translateY(6px);
        transition: opacity .3s ease, transform .3s ease;
    }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li {
        opacity: 1;
        transform: translateY(0);
    }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li:nth-child(1) { transition-delay: 40ms; }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li:nth-child(2) { transition-delay: 80ms; }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li:nth-child(3) { transition-delay: 120ms; }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li:nth-child(4) { transition-delay: 160ms; }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li:nth-child(5) { transition-delay: 200ms; }
    .main-header .main-menu .navigation > li.dropdown2:hover > ul > li:nth-child(n+6) { transition-delay: 240ms; }

    /* Ensure the dropdown stays above any decorative elements */
    .main-header .main-menu .navigation > li > ul { z-index: 999 !important; }
    .main-header .main-menu .navigation > li > ul > li > ul { z-index: 1000 !important; }

    /* Mobile/small viewport — drop the desktop hover behavior; let the mobile menu use its own UX */
    @media (max-width: 991px) {
        .main-header .main-menu .navigation > li > ul,
        .main-header .main-menu .navigation > li > ul.sub-menu {
            transform: none !important;
        }
    }
</style>

@php
    $siteLogo = \App\Models\Setting::get('site_logo');
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $logoUrl = $siteLogo ?: '/themes/kretaeiendom/images/logo/logo@2x.png';
    $phone = \App\Models\Setting::get('site_phone', '');
    $email = \App\Models\Setting::get('site_email', '');

    $headerMenu = app(\App\Services\FrontendMenuService::class)->get('header');
@endphp

<header id="header" class="main-header header-fixed fixed-header">
    <div class="header-lower">
        <div class="row">
            <div class="col-lg-12">
                <div class="inner-header">
                    <div class="inner-header-left">
                        <div class="logo-box flex">
                            <div class="logo">
                                <a href="{{ url('/') }}">
                                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" width="166" height="48" style="max-height:48px; object-fit:contain;">
                                </a>
                            </div>
                        </div>
                        <div class="nav-outer flex align-center">
                            <nav class="main-menu show navbar-expand-md">
                                <div class="navbar-collapse collapse clearfix" id="navbarSupportedContent">
                                    <ul class="navigation clearfix">
                                        @if($headerMenu)
                                            @foreach($headerMenu->rootItems as $item)
                                                <li class="{{ $item->children->count() ? 'dropdown2' : '' }} {{ Request::url() === $item->resolved_url ? 'current' : '' }}">
                                                    <a href="{{ $item->resolved_url }}" target="{{ $item->target }}">{{ $item->title }}</a>
                                                    @if($item->children->count())
                                                        <ul class="sub-menu">
                                                            @foreach($item->children as $child)
                                                                <li class="{{ $child->children->count() ? 'dropdown2' : '' }}">
                                                                    <a href="{{ $child->resolved_url }}" target="{{ $child->target }}">{{ $child->title }}</a>
                                                                    @if($child->children->count())
                                                                        <ul class="sub-menu">
                                                                            @foreach($child->children as $grandchild)
                                                                                <li><a href="{{ $grandchild->resolved_url }}" target="{{ $grandchild->target }}">{{ $grandchild->title }}</a></li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </div>

                    <div class="mobile-nav-toggler mobile-button"><span></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="close-btn"><span class="icon flaticon-cancel-1"></span></div>
    <div class="mobile-menu">
        <div class="menu-backdrop"></div>
        <nav class="menu-box">
            <div class="nav-logo">
                <a href="{{ url('/') }}">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" width="174" height="44" style="max-height:44px; object-fit:contain;">
                </a>
            </div>
            <div class="bottom-canvas">
                <div class="menu-outer">
                    <div class="mobile-nav-menu">
                        <ul class="navigation clearfix">
                            @if($headerMenu)
                                @foreach($headerMenu->rootItems as $item)
                                    <li class="{{ $item->children->count() ? 'dropdown2' : '' }}">
                                        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}">{{ $item->title }}</a>
                                        @if($item->children->count())
                                            <ul class="sub-menu">
                                                @foreach($item->children as $child)
                                                    <li class="{{ $child->children->count() ? 'dropdown2' : '' }}">
                                                        <a href="{{ $child->resolved_url }}" target="{{ $child->target }}">{{ $child->title }}</a>
                                                        @if($child->children->count())
                                                            <ul class="sub-menu">
                                                                @foreach($child->children as $grandchild)
                                                                    <li><a href="{{ $grandchild->resolved_url }}" target="{{ $grandchild->target }}">{{ $grandchild->title }}</a></li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="mobi-icon-box">
                    @if($phone)
                    <div class="box d-flex align-items-center">
                        <span class="icon icon-phone2"></span>
                        <div>{{ $phone }}</div>
                    </div>
                    @endif
                    @if($email)
                    <div class="box d-flex align-items-center">
                        <span class="icon icon-mail"></span>
                        <div>{{ $email }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </nav>
    </div>
</header>
