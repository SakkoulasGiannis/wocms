{{-- WYSIWYG Section — renders EditorJS JSON or HTML content --}}
@php
    // Prefer the resolved $content (passed in from render-section). Falls back
    // to reading $section->content for direct callers.
    if (isset($content) && is_array($content)) {
        $raw = $content['content'] ?? $content['html'] ?? $content['body'] ?? '';
    } else {
        $c = $section->content ?? [];
        $raw = is_string($c) ? $c : ($c['content'] ?? $c['html'] ?? $c['body'] ?? '');
    }
    if (! isset($settings) || ! is_array($settings)) {
        $settings = (array) ($section->settings ?? []);
    }

    // Container class resolution (most specific wins):
    //  1) $settings['container_class'] — explicit custom classes (empty string to disable)
    //  2) $settings['container_max_width'] — pick a preset like '6xl', '7xl', 'full', 'none'
    //  3) $settings['container'] === false → no wrapper class at all
    //  4) Default — 'mx-auto max-w-8xl px-4 sm:px-6 lg:px-8' (backward compat)
    if (array_key_exists('container_class', $settings)) {
        $containerClass = (string) $settings['container_class'];
    } elseif (! empty($settings['container_max_width'])) {
        $mw = $settings['container_max_width'];
        $containerClass = $mw === 'none'
            ? ''
            : "mx-auto max-w-{$mw} px-4 sm:px-6 lg:px-8";
    } elseif (($settings['container'] ?? true) === false) {
        $containerClass = '';
    } else {
        $containerClass = 'mx-auto max-w-8xl px-4 sm:px-6 lg:px-8';
    }

    // Padding resolution: explicit class wins over keyword.
    if (array_key_exists('padding_class', $settings)) {
        $paddingClass = (string) $settings['padding_class'];
    } else {
        $padding = $settings['padding'] ?? 'medium';
        $paddingClass = match ($padding) {
            'none'   => '',
            'small'  => 'py-8',
            'large'  => 'py-20',
            default  => 'py-12',
        };
    }

    $rendered = app(\App\Services\EditorJsRenderer::class)->toHtml($raw);
    // The EditorJS renderer escapes HTML by default — but our token resolver
    // outputs raw URLs/values. Nothing more needed; tokens were already swapped
    // before $content reached us.
@endphp

<section class="section-wysiwyg{{ $paddingClass ? ' '.$paddingClass : '' }}">
    @if($containerClass !== '')
        <div class="{{ $containerClass }}">
            {!! $rendered !!}
        </div>
    @else
        {!! $rendered !!}
    @endif
</section>
