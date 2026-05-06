@props(['content' => [], 'settings' => []])

@php
    $sectionClass = $content['section_class'] ?? 'py-16 bg-white';
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
    // Filter out empty rows
    $faqs = array_values(array_filter($faqs, fn ($f) => is_array($f) && trim((string) ($f['question'] ?? '')) !== ''));

    // Fallback content when nothing is configured
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
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($hasHeading)
                <div class="mx-auto max-w-2xl text-center mb-10">
                    @if ($subtitle !== '')
                        <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                    @endif
                    @if ($heading !== '')
                        <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                    @endif
                    @if ($description !== '')
                        <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                    @endif
                </div>
            @endif

            <ul class="space-y-3" x-data="{ open: null }">
                @foreach ($faqs as $i => $faq)
                    @php
                        $q = trim((string) ($faq['question'] ?? ''));
                        $a = trim((string) ($faq['answer'] ?? ''));
                    @endphp
                    <li class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all"
                        :class="open === {{ $i }} ? 'ring-1 ring-brand/30 shadow-md' : ''">
                        <button type="button"
                                @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                :aria-expanded="open === {{ $i }}"
                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50">
                            <span class="font-semibold text-slate-900"
                                  :class="open === {{ $i }} ? 'text-brand' : ''">{{ $q }}</span>
                            <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition-transform"
                                 :class="open === {{ $i }} ? 'rotate-180 text-brand' : ''"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open === {{ $i }}"
                             x-collapse
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             style="display: none">
                            <div class="border-t border-slate-100 px-5 py-4 text-slate-600 leading-relaxed">
                                {!! nl2br(e($a)) !!}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
