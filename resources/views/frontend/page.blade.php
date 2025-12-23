@extends('frontend.layout')

@section('title', $content->titles ?? $node->title ?? 'Page')

@section('content')
    <div class="bg-white">
        <!-- Featured Image Section -->
        @if(isset($content->featured_images) && $content->featured_images)
            <div class="w-full h-96 bg-gray-200 overflow-hidden">
                <img src="{{ $content->featured_images }}" alt="{{ $content->titles ?? 'Page' }}" class="w-full h-full object-cover">
            </div>
        @endif

        <!-- Page Title -->
        <div class="container mx-auto px-4 py-12">
            @if(isset($content->titles))
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-8">
                    {{ $content->titles }}
                </h1>
            @endif

            <!-- Page Content -->
            <div class="max-w-4xl mx-auto prose prose-lg">
                @if(isset($content->body))
                    {!! $content->body !!}
                @else
                    <div class="text-center text-gray-500 py-12">
                        <p>This page is empty. Edit it from the admin panel to add your content.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
