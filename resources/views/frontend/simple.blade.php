@extends('frontend.layout')

@section('title', $node->title ?? $title ?? 'Page')

@section('content')
    <div class="bg-white py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ $node->title }}</h1>

                @if(isset($html) && !empty($html))
                    <div class="prose prose-lg max-w-none">
                        {!! $html !!}
                    </div>
                @else
                    <div class="text-center text-gray-500 py-12">
                        <p>This page has no content yet. Edit it from the admin panel.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
