<style>
    .main-header .main-menu .navigation > li { padding-right: 18px; }
    .main-header .main-menu .navigation > li:last-child { padding-right: 0; }
    .main-header .main-menu .navigation > li > a { color: #0f507e; font-size: 15px; line-height: 19px; padding: 27px 10px; }
    .main-header .main-menu .navigation > li.dropdown2 > a::after { font-size: 14px; right: -16px; }
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
