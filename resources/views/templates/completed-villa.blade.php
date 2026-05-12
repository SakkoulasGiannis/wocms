@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', ($content->name ?? $title ?? 'Completed Villa') . ' — Completed Villas')

@section('content')
    @include('templates._partials.project-detail', [
        'listingUrl' => url('/completed-villas'),
        'listingLabel' => 'All Completed Villas',
    ])
@endsection
