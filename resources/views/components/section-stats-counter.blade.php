@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="stats-counter py-16 bg-blue-600 text-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-12">{{ $content['heading'] }}</h2>
        @endif

        <div class="grid md:grid-cols-{{ $settings['columns'] ?? 4 }} gap-8 text-center">
            @foreach($content['stats'] ?? [] as $stat)
                <div class="stat-item">
                    @if(!empty($stat['icon']))
                        <div class="text-4xl mb-3">
                            {!! $stat['icon'] !!}
                        </div>
                    @endif

                    <div class="text-5xl font-bold mb-2 {{ ($settings['animated'] ?? true) ? 'counter' : '' }}" data-target="{{ preg_replace('/[^0-9]/', '', $stat['number']) }}">
                        {{ $stat['number'] }}
                    </div>

                    <div class="text-xl">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if($settings['animated'] ?? true)
    <script>
        // Simple counter animation - can be enhanced
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.target || counter.textContent.replace(/\D/g, ''));
                if (target) {
                    let current = 0;
                    const increment = target / 100;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.textContent = target + '+';
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.floor(current) + '+';
                        }
                    }, 20);
                }
            });
        });
    </script>
@endif
