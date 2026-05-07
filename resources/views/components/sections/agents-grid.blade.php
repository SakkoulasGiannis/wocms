@props(['content' => [], 'settings' => []])

@php
    $ensureArray = function ($val, $default = []) {
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val) && $val !== '') {
            $d = json_decode($val, true);
            if (is_array($d)) {
                return $d;
            }
        }
        return $default;
    };

    $sectionClass = $content['section_class'] ?? 'py-20 lg:py-24 bg-surface';
    // Title / subtitle are OPTIONAL — empty by default so the section can stand alone.
    // The heading area only renders if at least one of them is non-empty.
    $subtitle = trim((string) ($content['subtitle'] ?? ''));
    $heading = trim((string) ($content['heading'] ?? $content['title'] ?? ''));
    $description = trim((string) ($content['description'] ?? ''));
    $hasHeading = $subtitle !== '' || $heading !== '' || $description !== '';
    $count = (int) ($settings['count'] ?? $content['count'] ?? 4);

    // Alignment of the grid items within their cells (Tailwind classes).
    // Accepts: 'left', 'center', 'right'. When fewer agents than columns,
    // this controls where the partial row sits visually.
    $alignment = strtolower(trim((string) ($content['alignment'] ?? 'left')));
    $alignmentClass = match ($alignment) {
        'center' => 'justify-items-center text-center',
        'right'  => 'justify-items-end text-right',
        default  => 'justify-items-start text-left',
    };

    $fallbackAgents = [
        [
            'name' => 'Nikos Papadakis',
            'title' => 'Property Specialist',
            'image' => '/themes/homelengo/images/agents/agent-5.jpg',
            'facebook' => '#',
            'twitter' => '#',
            'linkedin' => '#',
            'instagram' => '#',
            'phone' => '',
            'email' => '',
            'link' => '#',
        ],
        [
            'name' => 'Maria Stefanidou',
            'title' => 'Senior Agent',
            'image' => '/themes/homelengo/images/agents/agent-6.jpg',
            'facebook' => '#',
            'twitter' => '#',
            'linkedin' => '#',
            'instagram' => '#',
            'phone' => '',
            'email' => '',
            'link' => '#',
        ],
        [
            'name' => 'Kostas Antoniou',
            'title' => 'Rental Expert',
            'image' => '/themes/homelengo/images/agents/agent-7.jpg',
            'facebook' => '#',
            'twitter' => '#',
            'linkedin' => '#',
            'instagram' => '#',
            'phone' => '',
            'email' => '',
            'link' => '#',
        ],
        [
            'name' => 'Eleni Drakaki',
            'title' => 'Investment Consultant',
            'image' => '/themes/homelengo/images/agents/agent-8.jpg',
            'facebook' => '#',
            'twitter' => '#',
            'linkedin' => '#',
            'instagram' => '#',
            'phone' => '',
            'email' => '',
            'link' => '#',
        ],
    ];

    // Selected agent ids (from agents_picker field). Accepts array or JSON string.
    $selectedAgentIds = $content['agent_ids'] ?? $content['agents_ids'] ?? [];
    if (is_string($selectedAgentIds)) {
        $decoded = json_decode($selectedAgentIds, true);
        $selectedAgentIds = is_array($decoded) ? $decoded : [];
    }
    $selectedAgentIds = array_values(array_filter(array_map('intval', (array) $selectedAgentIds)));

    // Try database agents first
    $agents = [];
    if (class_exists(\App\Models\Agent::class)) {
        try {
            if (! empty($selectedAgentIds)) {
                // User explicitly picked specific agents — show only those, in the chosen order.
                $dbAgents = \App\Models\Agent::query()
                    ->whereIn('id', $selectedAgentIds)
                    ->where('active', true)
                    ->get()
                    ->sortBy(fn ($a) => array_search($a->id, $selectedAgentIds))
                    ->values();
            } else {
                // Fallback: first $count active agents by display order.
                $dbAgents = \App\Models\Agent::query()
                    ->where('active', true)
                    ->orderBy('order')
                    ->limit($count)
                    ->get();
            }

            if ($dbAgents->isNotEmpty()) {
                $agents = $dbAgents->map(function ($agent) {
                    return [
                        'name' => $agent->name,
                        'title' => $agent->role ?? 'Agent',
                        'bio' => $agent->bio ?? '',
                        'image' => (method_exists($agent, 'getMediumUrl') ? $agent->getMediumUrl() : null)
                            ?? $agent->getPhotoUrl()
                            ?? '/themes/homelengo/images/agents/agent-5.jpg',
                        'has_photo' => method_exists($agent, 'hasPhoto') ? $agent->hasPhoto() : (bool) ($agent->getPhotoUrl()),
                        'facebook' => $agent->facebook ?? '',
                        'twitter' => $agent->twitter ?? '',
                        'linkedin' => $agent->linkedin ?? '',
                        'instagram' => $agent->instagram ?? '',
                        'phone' => $agent->phone ?? '',
                        'email' => $agent->email ?? '',
                        'link' => url('/agents/' . $agent->slug),
                    ];
                })->toArray();
            }
        } catch (\Throwable $e) {
            $agents = [];
        }
    }

    // Fall back to content items
    if (empty($agents)) {
        $items = $ensureArray($content['items'] ?? $content['agents'] ?? null, []);
        if (! empty($items)) {
            $agents = array_map(function ($a) {
                return [
                    'name' => $a['name'] ?? '',
                    'title' => $a['title'] ?? $a['role'] ?? '',
                    'image' => $a['image'] ?? '/themes/homelengo/images/agents/agent-5.jpg',
                    'facebook' => $a['facebook'] ?? '',
                    'twitter' => $a['twitter'] ?? '',
                    'linkedin' => $a['linkedin'] ?? '',
                    'instagram' => $a['instagram'] ?? '',
                    'phone' => $a['phone'] ?? '',
                    'email' => $a['email'] ?? '',
                    'link' => $a['link'] ?? '#',
                ];
            }, $items);
        }
    }

    // Final fallback: static defaults
    if (empty($agents)) {
        $agents = $fallbackAgents;
    }

    // Slice only when there's no explicit picker selection — user-picked agents
    // should ALL display (the grid wraps to next rows automatically).
    if (empty($selectedAgentIds)) {
        $agents = array_slice($agents, 0, $count);
    }
@endphp

@if (! empty($agents))
    <section class="{{ $sectionClass }}">
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
            @if ($hasHeading)
                {{-- Header (only when subtitle / heading / description has content) --}}
                <div class="mx-auto max-w-2xl text-center mb-12">
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

            {{-- Agents grid — same card design as the /our-staff page --}}
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 {{ $alignmentClass }}">
                @foreach ($agents as $i => $agent)
                    <article
                        wire:key="agent-{{ $i }}"
                        class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-brand/30">
                        {{-- Photo — aspect-[4/5] with object-cover + object-top so faces stay
                             visible (any cropping happens at the bottom of the image, not the head) --}}
                        <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                            @if (! empty($agent['has_photo']) || ! empty($agent['image']))
                                <img src="{{ $agent['image'] }}" alt="{{ $agent['name'] }}"
                                     class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <svg class="h-24 w-24" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            @endif

                            {{-- Social icons overlay on hover --}}
                            @if (! empty($agent['facebook']) || ! empty($agent['instagram']) || ! empty($agent['linkedin']) || ! empty($agent['twitter']))
                                <div class="absolute inset-x-0 bottom-0 flex translate-y-full items-center justify-center gap-2 bg-gradient-to-t from-slate-900/80 to-transparent p-4 pt-10 opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                                    @if (! empty($agent['facebook']))
                                        <a href="{{ $agent['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"
                                           class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition-colors hover:bg-brand hover:text-white">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['instagram']))
                                        <a href="{{ $agent['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram"
                                           class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition-colors hover:bg-brand hover:text-white">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['linkedin']))
                                        <a href="{{ $agent['linkedin'] }}" target="_blank" rel="noopener" aria-label="LinkedIn"
                                           class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition-colors hover:bg-brand hover:text-white">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['twitter']))
                                        <a href="{{ $agent['twitter'] }}" target="_blank" rel="noopener" aria-label="Twitter / X"
                                           class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition-colors hover:bg-brand hover:text-white">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-lg font-bold text-slate-900 transition-colors group-hover:text-brand">
                                <a href="{{ $agent['link'] ?? '#' }}">{{ $agent['name'] }}</a>
                            </h3>
                            @if (! empty($agent['title']))
                                <p class="mt-0.5 text-sm font-medium text-brand">{{ $agent['title'] }}</p>
                            @endif

                            @if (! empty($agent['bio']))
                                <p class="mt-3 text-sm text-slate-600 line-clamp-3">{{ $agent['bio'] }}</p>
                            @endif

                            @if (! empty($agent['email']) || ! empty($agent['phone']))
                                <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                                    @if (! empty($agent['email']))
                                        <a href="mailto:{{ $agent['email'] }}" class="flex items-center gap-2 text-slate-600 transition-colors hover:text-brand">
                                            <svg class="h-4 w-4 flex-shrink-0 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span class="truncate">{{ $agent['email'] }}</span>
                                        </a>
                                    @endif
                                    @if (! empty($agent['phone']))
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $agent['phone']) }}" class="flex items-center gap-2 text-slate-600 transition-colors hover:text-brand">
                                            <svg class="h-4 w-4 flex-shrink-0 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            <span>{{ $agent['phone'] }}</span>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
