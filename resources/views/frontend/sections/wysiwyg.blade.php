{{-- WYSIWYG Section — renders EditorJS JSON or HTML content --}}
@php
    $c = $section->content ?? [];
    if (is_string($c)) {
        $raw = $c;
    } else {
        $raw = $c['content'] ?? $c['html'] ?? $c['body'] ?? '';
    }

    $containerClass = ($section->settings['container'] ?? true) ? 'mx-auto max-w-8xl px-4 sm:px-6 lg:px-8' : '';
    $padding = $section->settings['padding'] ?? 'medium';
    $paddingClass = match ($padding) {
        'small' => 'py-8',
        'large' => 'py-20',
        default => 'py-12',
    };

    $rendered = app(\App\Services\EditorJsRenderer::class)->toHtml($raw);
@endphp

<section class="section-wysiwyg {{ $paddingClass }}">
    <div class="{{ $containerClass }}">
        <div class="prose prose-lg max-w-none">
            {!! $rendered !!}
        </div>
    </div>
</section>
