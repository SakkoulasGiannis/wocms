@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $title ?? 'Renovations')

@section('content')
    @include('templates._partials.project-listing', [
        'subtitle' => 'Restoration & refurbishment',
        'description' => 'Renovation projects we have brought back to life — before-and-after transformations across Crete.',
    ])
@endsection
