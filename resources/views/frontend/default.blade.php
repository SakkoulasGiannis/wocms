@extends('frontend.layout')

@section('title', $node->title ?? $title ?? 'Page')

@section('content')
    {{-- Fallback view - should normally use generated blade file --}}
    @if($content && isset($content->html) && !empty($content->html))
        <div class="container mx-auto px-4 py-4 mb-4">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong class="font-bold">Notice:</strong>
                <span class="block sm:inline">Using fallback rendering. Save the entry again to generate blade template file.</span>
            </div>
        </div>
        {!! $content->html !!}
    @elseif($content && isset($content->content))
        {{-- Simple content --}}
        <div class="bg-white py-12">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ $node->title }}</h1>
                    <div class="prose prose-lg max-w-none">
                        {!! $content->content !!}
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-4xl mx-auto text-center text-gray-500">
                <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ $node->title }}</h1>
                <p>This page has no content yet. Edit it from the admin panel.</p>
            </div>
        </div>
    @endif
@endsection

{{-- Include page-specific CSS --}}
@if($content && isset($content->html_css) && !empty($content->html_css))
    @push('styles')
        <style>{!! $content->html_css !!}</style>
    @endpush
@endif
