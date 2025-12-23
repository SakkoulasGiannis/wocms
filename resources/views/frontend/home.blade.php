@extends('frontend.layout')

@section('title', isset($home) ? ($home->title ?? 'Home') : 'Home')

@section('content')
    @php
        $homeContent = isset($home) ? $home : null;
    @endphp

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-100 py-20">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                @if($homeContent && isset($homeContent->hero_titles))
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                        {{ $homeContent->hero_titles }}
                    </h1>
                @endif

                @if($homeContent && isset($homeContent->hero_subtitles))
                    <p class="text-xl md:text-2xl text-gray-700 mb-8">
                        {{ $homeContent->hero_subtitles }}
                    </p>
                @endif

                <div class="flex justify-center gap-4">
                    <a href="#content" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Learn More
                    </a>
                    <a href="/contact" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold border-2 border-blue-600 hover:bg-blue-50 transition">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div id="content" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto prose prose-lg">
                @if($homeContent && isset($homeContent->contents))
                    {!! $homeContent->contents !!}
                @else
                    <div class="text-center text-gray-500">
                        <p>Welcome to your new CMS! Edit this page from the admin panel to add your content.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
