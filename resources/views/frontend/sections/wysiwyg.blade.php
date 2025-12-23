{{-- WYSIWYG Section --}}
<section class="section-wysiwyg {{ $section->settings['container'] ?? true ? 'container mx-auto px-4' : '' }} py-{{ $section->settings['padding'] ?? 'medium' === 'small' ? '8' : ($section->settings['padding'] ?? 'medium' === 'large' ? '16' : '12') }}">
    <div class="prose prose-lg max-w-none">
        {!! $section->content !!}
    </div>
</section>
