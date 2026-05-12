@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $title ?? 'Under Construction')

@section('content')
    @include('templates._partials.project-listing', [
        'title' => 'Under Construction',
        'subtitle' => 'Projects in progress',
        'description' => 'Villa projects currently being built — follow the progress, reserve a future home, or get inspired.',
    ])
@endsection
