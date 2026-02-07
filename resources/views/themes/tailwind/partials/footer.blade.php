<!-- Footer -->
<footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                @php
                    $siteName = \App\Models\Setting::get('site_name', 'CMS');
                    $siteLogo = \App\Models\Setting::get('site_logo', '');
                    $siteDescription = \App\Models\Setting::get('site_description', 'Modern content management system built with Laravel');
                @endphp

                @if($siteLogo)
                    <div class="mb-4">
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-12 w-auto">
                    </div>
                @endif

                <h3 class="text-xl font-bold mb-4">{{ $siteName }}</h3>
                <p class="text-gray-400">{{ $siteDescription }}</p>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="/" class="text-gray-400 hover:text-white">Home</a></li>
                    @auth
                        <li><a href="/admin" class="text-gray-400 hover:text-white">Admin Panel</a></li>
                    @else
                        <li><a href="/login" class="text-gray-400 hover:text-white">Login</a></li>
                    @endauth
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">About</h4>
                <p class="text-gray-400">A flexible and modern CMS solution</p>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'CMS') }}. All rights reserved.</p>
            <p class="mt-2 text-sm">Built by <a href="https://weborange.gr" target="_blank" class="text-blue-400 hover:text-blue-300">WebOrange.gr</a></p>
        </div>
    </div>
</footer>
