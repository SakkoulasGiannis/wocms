<!-- Main Header -->
<header id="header" class="main-header header-fixed fixed-header">
    <!-- Header Lower -->
    <div class="header-lower">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                <div class="inner-header">
                    <div class="inner-header-left">
                        <div class="logo-box flex">
                            <div class="logo">
                                <a href="/">
                                    @php
                                        $siteLogo = \App\Models\Setting::get('site_logo', '');
                                        $siteName = \App\Models\Setting::get('site_name', 'CMS');
                                    @endphp
                                    @if($siteLogo)
                                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" width="166" height="48">
                                    @else
                                        <img src="/themes/bootstrap/images/logo/logo@2x.png" alt="{{ $siteName }}" width="166" height="48">
                                    @endif
                                </a>
                            </div>
                        </div>
                        <div class="nav-outer flex align-center">
                            <!-- Main Menu -->
                            <nav class="main-menu show navbar-expand-md">
                                <div class="navbar-collapse collapse clearfix" id="navbarSupportedContent">
                                    <ul class="navigation clearfix">
                                        <li class="dropdown2 home">
                                            <a href="/">Home</a>
                                        </li>

                                        @php
                                            $publicTemplates = \App\Models\Template::where('is_public', true)->get();
                                        @endphp

                                        @foreach($publicTemplates as $template)
                                            <li class="dropdown2">
                                                <a href="/{{ $template->slug }}">{{ $template->name }}</a>
                                            </li>
                                        @endforeach

                                        <li class="dropdown2">
                                            <a href="/blog">Blog</a>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                            <!-- Main Menu End-->
                        </div>
                    </div>
                    <div class="inner-header-right header-account">
                        @auth
                            <a href="/admin" class="tf-btn btn-line btn-login">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M13.1251 5C13.1251 5.8288 12.7959 6.62366 12.2099 7.20971C11.6238 7.79576 10.8289 8.125 10.0001 8.125C9.17134 8.125 8.37649 7.79576 7.79043 7.20971C7.20438 6.62366 6.87514 5.8288 6.87514 5C6.87514 4.1712 7.20438 3.37634 7.79043 2.79029C8.37649 2.20424 9.17134 1.875 10.0001 1.875C10.8289 1.875 11.6238 2.20424 12.2099 2.79029C12.7959 3.37634 13.1251 4.1712 13.1251 5ZM3.75098 16.765C3.77776 15.1253 4.44792 13.5618 5.61696 12.4117C6.78599 11.2616 8.36022 10.6171 10.0001 10.6171C11.6401 10.6171 13.2143 11.2616 14.3833 12.4117C15.5524 13.5618 16.2225 15.1253 16.2493 16.765C14.2888 17.664 12.1569 18.1279 10.0001 18.125C7.77014 18.125 5.65348 17.6383 3.75098 16.765Z"
                                        stroke="black" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                Admin Panel
                            </a>
                        @else
                            <a href="/login" class="tf-btn btn-line btn-login">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M13.1251 5C13.1251 5.8288 12.7959 6.62366 12.2099 7.20971C11.6238 7.79576 10.8289 8.125 10.0001 8.125C9.17134 8.125 8.37649 7.79576 7.79043 7.20971C7.20438 6.62366 6.87514 5.8288 6.87514 5C6.87514 4.1712 7.20438 3.37634 7.79043 2.79029C8.37649 2.20424 9.17134 1.875 10.0001 1.875C10.8289 1.875 11.6238 2.20424 12.2099 2.79029C12.7959 3.37634 13.1251 4.1712 13.1251 5ZM3.75098 16.765C3.77776 15.1253 4.44792 13.5618 5.61696 12.4117C6.78599 11.2616 8.36022 10.6171 10.0001 10.6171C11.6401 10.6171 13.2143 11.2616 14.3833 12.4117C15.5524 13.5618 16.2225 15.1253 16.2493 16.765C14.2888 17.664 12.1569 18.1279 10.0001 18.125C7.77014 18.125 5.65348 17.6383 3.75098 16.765Z"
                                        stroke="black" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                Sign in
                            </a>
                        @endauth
                    </div>

                    <div class="mobile-nav-toggler mobile-button"><span></span></div>

                </div>
            </div>
        </div>
        </div>
    </div>
    <!-- End Header Lower -->

    <!-- Mobile Menu  -->
    <div class="close-btn"><span class="icon flaticon-cancel-1"></span></div>
    <div class="mobile-menu">
        <div class="menu-backdrop"></div>
        <nav class="menu-box">
            <div class="nav-logo">
                <a href="/">
                    @if($siteLogo ?? false)
                        <img src="{{ $siteLogo }}" alt="{{ $siteName ?? 'CMS' }}" width="174" height="44">
                    @else
                        <img src="/themes/bootstrap/images/logo/logo@2x.png" alt="{{ $siteName ?? 'CMS' }}" width="174" height="44">
                    @endif
                </a>
            </div>
            <div class="bottom-canvas">
                @guest
                    <div class="login-box flex align-center">
                        <a href="/login">Login</a>
                        <span>/</span>
                        <a href="/register">Register</a>
                    </div>
                @endguest
                <div class="menu-outer"></div>
                @php
                    $sitePhone = \App\Models\Setting::get('site_phone', '');
                    $siteEmail = \App\Models\Setting::get('site_email', '');
                @endphp
                @if($sitePhone || $siteEmail)
                    <div class="mobi-icon-box">
                        @if($sitePhone)
                            <div class="box d-flex align-items-center">
                                <span class="icon icon-phone2"></span>
                                <div>{{ $sitePhone }}</div>
                            </div>
                        @endif
                        @if($siteEmail)
                            <div class="box d-flex align-items-center">
                                <span class="icon icon-mail"></span>
                                <div>{{ $siteEmail }}</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </nav>
    </div>
    <!-- End Mobile Menu -->

</header>
<!-- End Main Header -->
