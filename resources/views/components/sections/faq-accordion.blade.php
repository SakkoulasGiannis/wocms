@props(['content' => [], 'settings' => []])

@php
    $sectionClass = $content['section_class'] ?? 'py-20 lg:py-24 bg-white';
    $subtitle = trim((string) ($content['subtitle'] ?? ''));
    $heading  = trim((string) ($content['heading'] ?? $content['title'] ?? ''));
    $description = trim((string) ($content['description'] ?? ''));
    $hasHeading = $subtitle !== '' || $heading !== '' || $description !== '';

    // FAQs come from a repeater field
    $faqs = $content['faqs'] ?? [];
    if (! is_array($faqs)) {
        $decoded = is_string($faqs) ? json_decode($faqs, true) : null;
        $faqs = is_array($decoded) ? $decoded : [];
    }
    $faqs = array_values(array_filter($faqs, fn ($f) => is_array($f) && trim((string) ($f['question'] ?? '')) !== ''));

    if (empty($faqs)) {
        $faqs = [
            ['question' => 'Why should I use your services?', 'answer' => 'Once your account is set up and you\'ve familiarized yourself with the platform, you are ready to start using our services.'],
            ['question' => 'How do I get started with your services?', 'answer' => 'Sign up, verify your email, and complete the onboarding wizard — takes about 2 minutes.'],
            ['question' => 'What kind of support do you offer?', 'answer' => 'We provide email support 24/7 and live chat during business hours.'],
        ];
    }
@endphp

@if (! empty($faqs))
    <section class="{{ $sectionClass }}">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if ($hasHeading)
                <div class="mx-auto max-w-3xl text-center mb-10">
                    @if ($subtitle !== '')
                        <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                    @endif
                    @if ($heading !== '')
                        <h2 class="mt-3 text-3xl md:text-4xl font-extrabold text-slate-900 capitalize">{{ $heading }}</h2>
                    @endif
                    @if ($description !== '')
                        <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                    @endif
                </div>
            @endif

            {{-- Homelengo-style accordion: white card per item, +/− icon on the right --}}
            <ul class="w-full space-y-4" x-data="{ open: null }">
                @foreach ($faqs as $i => $faq)
                    @php
                        $q = trim((string) ($faq['question'] ?? ''));
                        $a = trim((string) ($faq['answer'] ?? ''));
                    @endphp
                    <li class="rounded-2xl border border-[#e4e4e4] bg-white overflow-hidden transition-all duration-300 ease-out"
                        :class="open === {{ $i }} ? 'shadow-[0_10px_30px_rgba(15,23,42,0.10)] border-transparent' : 'shadow-none'">
                        <button type="button"
                                @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                :aria-expanded="open === {{ $i }}"
                                class="relative w-full text-left pl-7 pr-16 py-6 font-semibold text-lg leading-7 text-[#161e2d] capitalize">
                            <span>{{ $q }}</span>
                            {{-- + / − icon on the right (uses pure SVG so no icomoon dependency) --}}
                            <span aria-hidden="true"
                                  class="absolute right-7 top-1/2 -translate-y-1/2 inline-flex h-6 w-6 items-center justify-center text-[#161e2d] transition-transform duration-500 ease-out">
                                {{-- horizontal bar always shown --}}
                                <svg class="absolute" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <line x1="3" y1="10" x2="17" y2="10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                </svg>
                                {{-- vertical bar — rotates / disappears when open --}}
                                <svg class="absolute transition-transform duration-300 ease-out"
                                     :class="open === {{ $i }} ? 'rotate-90 opacity-0' : 'rotate-0 opacity-100'"
                                     width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <line x1="10" y1="3" x2="10" y2="17" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </button>
                        <div x-show="open === {{ $i }}"
                             x-collapse
                             style="display: none">
                            <div class="px-7 pb-6 pt-0 text-[#5d6573] leading-relaxed">
                                {!! nl2br(e($a)) !!}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
