@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $title ?? 'Completed Villas')

@section('content')
    @include('templates._partials.project-listing', [
        'subtitle' => 'Our completed work',
        'description' => 'Browse our portfolio of finished villa projects across Crete — quality construction, thoughtful design.',
    ])
@endsection
