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

    $containerClass = ($settings['container'] ?? true) ? 'mx-auto max-w-8xl px-4 sm:px-6 lg:px-8' : '';
    $padding = $settings['padding'] ?? 'medium';
    $paddingClass = match ($padding) {
        'small' => 'py-8',
        'large' => 'py-20',
        default => 'py-12',
    };

    $rendered = app(\App\Services\EditorJsRenderer::class)->toHtml($raw);
    // The EditorJS renderer escapes HTML by default — but our token resolver
    // outputs raw URLs/values. Nothing more needed; tokens were already swapped
    // before $content reached us.
@endphp

<section class="section-wysiwyg {{ $paddingClass }}">
    <div class="{{ $containerClass }}">
        <div class="prose prose-lg max-w-none">
            {!! $rendered !!}
        </div>
    </div>
</section>
