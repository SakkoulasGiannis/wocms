@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $node->title ?? $title ?? 'Page')

@section('content')
    {{-- Render all active sections --}}
    @if(isset($sections) && $sections->count() > 0)
        @foreach($sections as $section)
            @include('partials.render-section', ['section' => $section])
        @endforeach
    @else
        <div class="container mx-auto px-4 py-12">
            <div class="text-center text-gray-500">
                <p>This page has no sections yet. Add sections from the admin panel.</p>
            </div>
        </div>
    @endif
@endsection
