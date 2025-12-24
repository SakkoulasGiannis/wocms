<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\Setting::get('site_name', 'WOCMS') }} - Coming Soon</title>

    <!-- Favicon -->
    @if(\App\Models\Setting::get('site_favicon'))
        <link rel="icon" type="image/x-icon" href="{{ \App\Models\Setting::get('site_favicon') }}">
    @endif

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full text-center">
        <!-- Logo -->
        @if(\App\Models\Setting::get('site_logo'))
            <div class="mb-8">
                <img src="{{ \App\Models\Setting::get('site_logo') }}"
                     alt="{{ \App\Models\Setting::get('site_name', 'WOCMS') }}"
                     class="mx-auto h-20 object-contain">
            </div>
        @endif

        <!-- Construction Icon -->
        <div class="mb-8 float-animation">
            <svg class="mx-auto h-32 w-32 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
            </svg>
        </div>

        <!-- Main Heading -->
        <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-4">
            We're Building Something<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
                Exciting
            </span>
        </h1>

        <!-- Description -->
        <p class="text-xl text-gray-600 mb-8">
            {{ \App\Models\Setting::get('site_description', 'Our website is currently under construction. We\'ll be launching soon!') }}
        </p>

        <!-- Coming Soon Badge -->
        <div class="inline-flex items-center px-6 py-3 rounded-full bg-blue-100 text-blue-800 font-semibold mb-12">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
            Coming Soon
        </div>

        <!-- Progress Bar -->
        <div class="max-w-md mx-auto mb-8">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Progress</span>
                <span>85%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-2.5 rounded-full" style="width: 85%"></div>
            </div>
        </div>

        <!-- Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
            <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Fast & Modern</h3>
                <p class="text-sm text-gray-600">Built with the latest technologies</p>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Secure</h3>
                <p class="text-sm text-gray-600">Your data is safe with us</p>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">User Friendly</h3>
                <p class="text-sm text-gray-600">Intuitive and easy to use</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-16 text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'WOCMS') }}. All rights reserved.</p>
            <p class="mt-2 text-xs text-gray-400">
                Built with <a href="https://weborange.gr" target="_blank" class="text-blue-600 hover:underline font-medium">WebOrange CMS</a>
            </p>
            @if(config('app.env') !== 'production')
                <p class="mt-2 text-xs text-gray-400">
                    Admin users can access the site. <a href="/admin" class="text-blue-600 hover:underline">Go to Admin</a>
                </p>
            @endif
        </div>
    </div>
</body>
</html>
