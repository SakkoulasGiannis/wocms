@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', ($content->name ?? $title ?? 'Project') . ' — Under Construction')

@section('content')
    @include('templates._partials.project-detail', [
        'listingUrl' => url('/under-construction'),
        'listingLabel' => 'All Under Construction',
    ])
@endsection
