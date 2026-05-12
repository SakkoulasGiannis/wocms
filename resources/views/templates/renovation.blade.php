@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', ($content->name ?? $title ?? 'Renovation') . ' — Renovations')

@section('content')
    @include('templates._partials.project-detail', [
        'listingUrl' => url('/renovations'),
        'listingLabel' => 'All Renovations',
    ])
@endsection
