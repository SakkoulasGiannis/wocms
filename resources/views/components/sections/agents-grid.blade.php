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

    $sectionClass = $content['section_class'] ?? 'py-16 bg-slate-50';
    $subtitle = $content['subtitle'] ?? 'Our Teams';
    $heading = $content['heading'] ?? $content['title'] ?? 'Meet Our Agents';
    $description = $content['description'] ?? '';
    $count = (int) ($settings['count'] ?? $content['count'] ?? 4);

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
                        'image' => $agent->getPhotoUrl() ?? '/themes/homelengo/images/agents/agent-5.jpg',
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

    $agents = array_slice($agents, 0, $count);
@endphp

@if (! empty($agents))
    <section class="{{ $sectionClass }}">
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="mx-auto max-w-2xl text-center mb-12">
                @if ($subtitle)
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                @endif
                @if ($heading)
                    <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                @endif
                @if ($description)
                    <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                @endif
            </div>

            {{-- Agents grid --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($agents as $i => $agent)
                    <div
                        class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                        wire:key="agent-{{ $i }}"
                    >
                        {{-- Image --}}
                        <a href="{{ $agent['link'] ?? '#' }}" class="block relative overflow-hidden aspect-[4/5] bg-slate-100">
                            <img
                                src="{{ $agent['image'] }}"
                                alt="{{ $agent['name'] }}"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                loading="lazy"
                            >

                            {{-- Social overlay --}}
                            <div class="absolute inset-x-0 bottom-0 translate-y-full bg-gradient-to-t from-slate-900/80 via-slate-900/40 to-transparent p-4 transition-transform duration-300 group-hover:translate-y-0">
                                <div class="flex items-center justify-center gap-3">
                                    @if (! empty($agent['facebook']))
                                        <a
                                            href="{{ $agent['facebook'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 transition-colors hover:bg-brand hover:text-white"
                                            aria-label="Facebook"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['twitter']))
                                        <a
                                            href="{{ $agent['twitter'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 transition-colors hover:bg-brand hover:text-white"
                                            aria-label="X / Twitter"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['linkedin']))
                                        <a
                                            href="{{ $agent['linkedin'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 transition-colors hover:bg-brand hover:text-white"
                                            aria-label="LinkedIn"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['instagram']))
                                        <a
                                            href="{{ $agent['instagram'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 transition-colors hover:bg-brand hover:text-white"
                                            aria-label="Instagram"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.849.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.849.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </a>

                        {{-- Info --}}
                        <div class="flex items-center justify-between gap-4 p-5">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-slate-900">
                                    <a href="{{ $agent['link'] ?? '#' }}" class="transition-colors hover:text-brand">
                                        {{ $agent['name'] }}
                                    </a>
                                </h3>
                                @if (! empty($agent['title']))
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $agent['title'] }}</p>
                                @endif
                            </div>

                            @if (! empty($agent['phone']) || ! empty($agent['email']))
                                <div class="flex shrink-0 items-center gap-2">
                                    @if (! empty($agent['phone']))
                                        <a
                                            href="tel:{{ $agent['phone'] }}"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition-colors hover:bg-brand hover:text-white"
                                            aria-label="Call {{ $agent['name'] }}"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                            </svg>
                                        </a>
                                    @endif
                                    @if (! empty($agent['email']))
                                        <a
                                            href="mailto:{{ $agent['email'] }}"
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition-colors hover:bg-brand hover:text-white"
                                            aria-label="Email {{ $agent['name'] }}"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
