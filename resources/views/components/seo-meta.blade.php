@props(['entry' => null, 'title' => null, 'description' => null])

{{-- ========================================= --}}
{{-- SEO Meta Tags Component --}}
{{-- ========================================= --}}

@php
    // Treat empty strings the same as null so the fallback chain actually kicks in.
    $firstNonEmpty = fn (...$vals) => collect($vals)->map(fn ($v) => is_string($v) ? trim($v) : $v)->first(fn ($v) => $v !== null && $v !== '');

    // Basic SEO values — fall through entry SEO → entry title/name → component prop → site name / description
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteTagline = \App\Models\Setting::get('site_tagline', '');
    $siteDescription = \App\Models\Setting::get('site_description', '');

    $entryTitle = $entry?->title ?? $entry?->name ?? $entry?->heading ?? null;
    $entryExcerpt = $entry?->excerpt ?? $entry?->description ?? $entry?->summary ?? null;

    $seoTitle = $firstNonEmpty($entry?->seo_title, $title, $entryTitle, $siteName) ?: $siteName;
    // Compose a sensible suffix when we have both entry title and site name
    if ($seoTitle && $entryTitle && $seoTitle === $entryTitle && $siteName && stripos($seoTitle, $siteName) === false) {
        $seoTitle = $entryTitle . ' — ' . $siteName;
    }

    $seoDescription = $firstNonEmpty($entry?->seo_description, $description, $entryExcerpt, $siteTagline, $siteDescription);
    if ($seoDescription && is_string($seoDescription)) {
        $seoDescription = \Illuminate\Support\Str::limit(strip_tags($seoDescription), 160);
    }

    $seoKeywords = $entry?->seo_keywords ?: '';
    $seoCanonicalUrl = $firstNonEmpty($entry?->seo_canonical_url) ?: url()->current();
    $seoRobotsIndex = $firstNonEmpty($entry?->seo_robots_index) ?: 'index';
    $seoRobotsFollow = $firstNonEmpty($entry?->seo_robots_follow) ?: 'follow';

    // Resolve image URL safely
    $resolveImage = function($value) use ($entry) {
        if (is_string($value)) {
            return $value;
        }
        if (is_object($value) && method_exists($value, 'getUrl')) {
            return $value->getUrl();
        }
        if ($entry?->featured_image_url ?? false) {
            return $entry->featured_image_url;
        }
        return asset('images/og-default.jpg');
    };

    // Open Graph
    $ogTitle = $entry?->seo_og_title ?? $seoTitle;
    $ogDescription = $entry?->seo_og_description ?? $seoDescription;
    $ogImage = $resolveImage($entry?->seo_og_image ?? $entry?->featured_image);
    $ogType = $entry?->seo_og_type ?? 'website';
    $ogUrl = $entry?->seo_og_url ?? url()->current();

    // Twitter Card
    $twitterCard = $entry?->seo_twitter_card ?? 'summary_large_image';
    $twitterTitle = $entry?->seo_twitter_title ?? $seoTitle;
    $twitterDescription = $entry?->seo_twitter_description ?? $seoDescription;
    $twitterImage = $resolveImage($entry?->seo_twitter_image ?? $ogImage);
    $twitterSite = $entry?->seo_twitter_site ?? '';
    $twitterCreator = $entry?->seo_twitter_creator ?? '';

    // Schema.org JSON-LD (safe array method)
    $schemaType = $entry?->seo_schema_type ?? '';
    $schemaCustom = $entry?->seo_schema_custom ?? '';

    $schema = [];

    if ($schemaType) {
        $schema = [
            '@context'   => 'https://schema.org',
            '@type'      => $schemaType,
            'headline'   => $seoTitle,
            'description'=> $seoDescription,
            'image'      => $ogImage,
            'url'        => url()->current(),
        ];

        if ($entry && isset($entry->author)) {
            $schema['author'] = [
                '@type' => 'Person',
                'name'  => $entry->author
            ];
        }

        if ($entry && isset($entry->published_at)) {
            $schema['datePublished'] = $entry->published_at;
        }

        if ($entry && isset($entry->updated_at)) {
            $schema['dateModified'] = $entry->updated_at;
        }
    }
@endphp

{{-- ========================================= --}}
{{-- Basic SEO Meta Tags --}}
{{-- ========================================= --}}

<title>{{ $seoTitle }}</title>

@if($seoDescription)
    <meta name="description" content="{{ $seoDescription }}">
@endif

@if($seoKeywords)
    <meta name="keywords" content="{{ $seoKeywords }}">
@endif

<meta name="robots" content="{{ $seoRobotsIndex }}, {{ $seoRobotsFollow }}">
<link rel="canonical" href="{{ $seoCanonicalUrl }}">

{{-- ========================================= --}}
{{-- Open Graph --}}
{{-- ========================================= --}}
<meta property="og:title" content="{{ $ogTitle }}">
@if($ogDescription)
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:site_name" content="{{ config('app.name') }}">

{{-- ========================================= --}}
{{-- Twitter Card --}}
{{-- ========================================= --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ $twitterTitle }}">
@if($twitterDescription)
    <meta name="twitter:description" content="{{ $twitterDescription }}">
@endif
<meta name="twitter:image" content="{{ $twitterImage }}">
@if($twitterSite)
    <meta name="twitter:site" content="{{ $twitterSite }}">
@endif
@if($twitterCreator)
    <meta name="twitter:creator" content="{{ $twitterCreator }}">
@endif

{{-- ========================================= --}}
{{-- JSON-LD Schema.org --}}
{{-- ========================================= --}}
@if($schemaCustom)
    {{-- Custom raw schema --}}
    <script type="application/ld+json">{!! $schemaCustom !!}</script>
@elseif($schemaType)
    {{-- Safe encoded schema --}}
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif
