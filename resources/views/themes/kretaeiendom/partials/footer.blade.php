@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteLogo = \App\Models\Setting::get('site_logo', '');
    $siteDescription = \App\Models\Setting::get('site_description', 'Specializes in providing high-class real estate services in Crete, Greece.');
    $phone = \App\Models\Setting::get('site_phone', '');
    $email = \App\Models\Setting::get('site_email', '');
    $address = \App\Models\Setting::get('site_address', '');
    $facebook = \App\Models\Setting::get('social_facebook', '');
    $instagram = \App\Models\Setting::get('social_instagram', '');
    $twitter = \App\Models\Setting::get('social_twitter', '');
    $linkedin = \App\Models\Setting::get('social_linkedin', '');
    $youtube = \App\Models\Setting::get('social_youtube', '');

    // Get footer menu if assigned
    $footerMenu = \App\Models\Menu::where('location', 'footer')->first();
    $footerItems = $footerMenu ? $footerMenu->items()->whereNull('parent_id')->orderBy('order')->get() : collect();
@endphp

<footer class="footer">
    <div class="top-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-cl-1">
                        @if($siteLogo)
                            <a href="{{ url('/') }}" class="d-inline-block mb-12">
                                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height:40px;">
                            </a>
                        @endif
                        <p class="text-variant-2">{{ $siteDescription }}</p>
                        <ul class="mt-12">
                            @if($address)
                            <li class="mt-12 d-flex align-items-center gap-8">
                                <i class="icon icon-mapPinLine fs-20 text-variant-2"></i>
                                <p class="text-white">{{ $address }}</p>
                            </li>
                            @endif
                            @if($phone)
                            <li class="mt-12 d-flex align-items-center gap-8">
                                <i class="icon icon-phone2 fs-20 text-variant-2"></i>
                                <a href="tel:{{ $phone }}" class="text-white caption-1">{{ $phone }}</a>
                            </li>
                            @endif
                            @if($email)
                            <li class="mt-12 d-flex align-items-center gap-8">
                                <i class="icon icon-mail fs-20 text-variant-2"></i>
                                <a href="mailto:{{ $email }}" class="text-white">{{ $email }}</a>
                            </li>
                            @endif
                        </ul>

                        {{-- Social Media Icons --}}
                        @if($facebook || $instagram || $twitter || $linkedin || $youtube)
                        <ul class="mt-20 d-flex gap-12">
                            @if($facebook)<li><a href="{{ $facebook }}" target="_blank" class="text-variant-2"><i class="icon icon-facebook"></i></a></li>@endif
                            @if($instagram)<li><a href="{{ $instagram }}" target="_blank" class="text-variant-2"><i class="icon icon-instagram"></i></a></li>@endif
                            @if($twitter)<li><a href="{{ $twitter }}" target="_blank" class="text-variant-2"><i class="icon icon-twitter"></i></a></li>@endif
                            @if($linkedin)<li><a href="{{ $linkedin }}" target="_blank" class="text-variant-2"><i class="icon icon-linkedin"></i></a></li>@endif
                            @if($youtube)<li><a href="{{ $youtube }}" target="_blank" class="text-variant-2"><i class="icon icon-youtube"></i></a></li>@endif
                        </ul>
                        @endif
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <div class="footer-cl-2 footer-col-block">
                        <div class="fw-7 text-white footer-heading-mobile">Quick Links</div>
                        <div class="tf-collapse-content">
                            <ul class="mt-10 navigation-menu-footer">
                                @if($footerItems->isNotEmpty())
                                    @foreach($footerItems as $item)
                                        <li><a href="{{ $item->resolved_url ?? $item->url }}" class="caption-1 text-variant-2" @if($item->target === '_blank') target="_blank" @endif>{{ $item->title }}</a></li>
                                    @endforeach
                                @else
                                    <li><a href="{{ url('/') }}" class="caption-1 text-variant-2">Home</a></li>
                                    <li><a href="{{ url('/properties') }}" class="caption-1 text-variant-2">Properties</a></li>
                                    <li><a href="{{ url('/rental-properties') }}" class="caption-1 text-variant-2">Rentals</a></li>
                                    <li><a href="{{ url('/blog') }}" class="caption-1 text-variant-2">Blog</a></li>
                                    <li><a href="{{ url('/contact') }}" class="caption-1 text-variant-2">Contact</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <div class="footer-cl-3 footer-col-block">
                        <div class="fw-7 text-white footer-heading-mobile">Properties</div>
                        <div class="tf-collapse-content">
                            <ul class="mt-10 navigation-menu-footer">
                                <li><a href="{{ url('/properties?status=for_sale') }}" class="caption-1 text-variant-2">For Sale</a></li>
                                <li><a href="{{ url('/rental-properties') }}" class="caption-1 text-variant-2">For Rent</a></li>
                                <li><a href="{{ url('/agents') }}" class="caption-1 text-variant-2">Our Agents</a></li>
                                <li><a href="{{ url('/contact') }}" class="caption-1 text-variant-2">Contact Us</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="footer-cl-4 footer-col-block">
                        <div class="fw-7 text-white footer-heading-mobile">Newsletter</div>
                        <div class="tf-collapse-content">
                            <p class="mt-12 text-variant-2">Your Weekly/Monthly Dose of Knowledge and Inspiration</p>
                            <form class="mt-12" id="subscribe-form" action="#" method="post">
                                @csrf
                                <div id="subscribe-content">
                                    <input type="email" name="email" id="subscribe-email" placeholder="Your email address" required>
                                    <button type="submit" id="subscribe-button" class="button-subscribe">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M5.00044 9.99935L2.72461 2.60352C8.16867 4.18685 13.3024 6.68806 17.9046 9.99935C13.3027 13.3106 8.16921 15.8118 2.72544 17.3952L5.00044 9.99935ZM5.00044 9.99935H11.2504" stroke="#1563DF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bottom-footer">
        <div class="container">
            <div class="content-footer-bottom">
                <div class="copyright">&copy;{{ date('Y') }} {{ $siteName }}. All Rights Reserved.</div>
                <ul class="menu-bottom">
                    <li><a href="{{ url('/terms') }}">Terms Of Services</a></li>
                    <li><a href="{{ url('/privacy-policy') }}">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- go top -->
<div class="progress-wrap">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
        <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" style="transition: stroke-dashoffset 10ms linear 0s; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 286.138;"></path>
    </svg>
</div>
