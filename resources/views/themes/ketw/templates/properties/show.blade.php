@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $property->meta_title ?: $property->title)

@section('content')
    @include('themes.ketw.templates.properties._show-body', ['isRental' => false])
@endsection
