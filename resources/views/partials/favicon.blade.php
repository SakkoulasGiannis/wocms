@php
    $siteFavicon = \App\Models\Setting::get('site_favicon');
    $siteFaviconPng = \App\Models\Setting::get('site_favicon_png');
@endphp
@if($siteFavicon && str_ends_with(strtolower($siteFavicon), '.svg'))
    <link rel="icon" type="image/svg+xml" href="{{ $siteFavicon }}">
    @if($siteFaviconPng)
        <link rel="icon" type="image/png" href="{{ $siteFaviconPng }}">
        <link rel="apple-touch-icon" href="{{ $siteFaviconPng }}">
    @endif
@elseif($siteFavicon)
    <link rel="icon" type="image/x-icon" href="{{ $siteFavicon }}">
    @if($siteFaviconPng)
        <link rel="apple-touch-icon" href="{{ $siteFaviconPng }}">
    @endif
@elseif($siteFaviconPng)
    <link rel="icon" type="image/png" href="{{ $siteFaviconPng }}">
    <link rel="apple-touch-icon" href="{{ $siteFaviconPng }}">
@endif
