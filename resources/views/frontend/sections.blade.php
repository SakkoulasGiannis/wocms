@extends('frontend.layout')

@section('title', $node->title ?? $title ?? 'Page')

@section('content')
    {{-- Render all active sections --}}
    @if(isset($sections) && $sections->count() > 0)
        @foreach($sections as $section)
            {{-- Use rendered_html if available (template-based), otherwise fallback to old method --}}
            @if(!empty($section->rendered_html))
                {!! $section->rendered_html !!}
            @elseif(view()->exists('frontend.sections.' . $section->section_type))
                @include('frontend.sections.' . $section->section_type, ['section' => $section])
            @else
                <div class="container mx-auto px-4 py-8">
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                        <p>Section type "{{ $section->section_type }}" not found.</p>
                    </div>
                </div>
            @endif
        @endforeach
    @else
        <div class="container mx-auto px-4 py-12">
            <div class="text-center text-gray-500">
                <p>This page has no sections yet. Add sections from the admin panel.</p>
            </div>
        </div>
    @endif
@endsection
