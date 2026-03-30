@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Our Teams';
    $title = $content['title'] ?? 'Meet Our Agents';
    $footerText = $content['footer_text'] ?? 'Become an agent and get the commission you deserve.';
    $footerLinkText = $content['footer_link_text'] ?? 'Contact us';
    $footerLink = $content['footer_link'] ?? '#';
    $limit = $content['limit'] ?? 8;
    $agentIds = $content['agent_ids'] ?? [];

    // Try to load real agents from the database
    $dbAgents = collect();
    try {
        $query = \App\Models\Agent::active()->orderBy('order');
        if (!empty($agentIds)) {
            // Show specific agents
            $query->whereIn('id', $agentIds);
        }
        $dbAgents = $query->limit($limit)->get();
    } catch (\Exception $e) {}

    // Build agents array from DB or fallback
    if ($dbAgents->isNotEmpty()) {
        $agents = $dbAgents->map(function ($agent) {
            $socials = [];
            if ($agent->facebook) $socials[] = ['platform' => 'facebook', 'url' => $agent->facebook];
            if ($agent->twitter) $socials[] = ['platform' => 'x', 'url' => $agent->twitter];
            if ($agent->linkedin) $socials[] = ['platform' => 'linkedin', 'url' => $agent->linkedin];
            if ($agent->instagram) $socials[] = ['platform' => 'instagram', 'url' => $agent->instagram];

            return [
                'name' => $agent->name,
                'role' => $agent->role ?? 'Agent',
                'image' => $agent->getPhotoUrl() ?? '/themes/kretaeiendom/images/agents/agent-5.jpg',
                'link' => url('/agents/' . $agent->slug),
                'email' => $agent->email,
                'phone' => $agent->phone,
                'socials' => $socials,
            ];
        })->toArray();
    } else {
        // Static fallback
        $agents = $content['agents'] ?? [
            [
                'name' => 'Chris Patt',
                'role' => 'Administrative Staff',
                'image' => '/themes/kretaeiendom/images/agents/agent-5.jpg',
                'link' => '#',
                'socials' => [['platform' => 'facebook', 'url' => '#'], ['platform' => 'linkedin', 'url' => '#']],
            ],
            [
                'name' => 'Marvin McKinney',
                'role' => 'Administrative Staff',
                'image' => '/themes/kretaeiendom/images/agents/agent-6.jpg',
                'link' => '#',
                'socials' => [['platform' => 'facebook', 'url' => '#'], ['platform' => 'linkedin', 'url' => '#']],
            ],
            [
                'name' => 'Wade Warren',
                'role' => 'Administrative Staff',
                'image' => '/themes/kretaeiendom/images/agents/agent-7.jpg',
                'link' => '#',
                'socials' => [['platform' => 'facebook', 'url' => '#'], ['platform' => 'linkedin', 'url' => '#']],
            ],
            [
                'name' => 'Devon Lane',
                'role' => 'Administrative Staff',
                'image' => '/themes/kretaeiendom/images/agents/agent-8.jpg',
                'link' => '#',
                'socials' => [['platform' => 'facebook', 'url' => '#'], ['platform' => 'linkedin', 'url' => '#']],
            ],
        ];
    }
    $delays = ['.2s', '.3s', '.4s', '.5s', '.6s', '.7s', '.8s', '.9s'];
@endphp

<!-- Agents -->
<section class="flat-section flat-agents">
    <div class="container">
        <div class="box-title text-center wow fadeInUp">
            <div class="text-subtitle text-primary">{{ $subtitle }}</div>
            <h3 class="title mt-4">{{ $title }}</h3>
        </div>
        <div dir="ltr" class="swiper tf-sw-mobile-1" data-screen="575" data-preview="1" data-space="15">
            <div class="tf-layout-mobile-sm xl-col-4 sm-col-2 swiper-wrapper">
                @foreach($agents as $index => $agent)
                    <div class="swiper-slide">
                        <div class="box-agent hover-img wow fadeInUp" data-wow-delay="{{ $delays[$index] ?? '.2s' }}">
                            <a href="{{ $agent['link'] }}" class="box-img img-style">
                                <img class="lazyload" data-src="{{ $agent['image'] }}" src="{{ $agent['image'] }}" alt="image-agent">
                                <ul class="agent-social">
                                    @foreach($agent['socials'] ?? [] as $social)
                                        @php
                                            $platform = is_array($social) ? ($social['platform'] ?? 'facebook') : $social;
                                            $socialUrl = is_array($social) ? ($social['url'] ?? '#') : '#';
                                        @endphp
                                        <li><a href="{{ $socialUrl }}" target="_blank"><span class="icon icon-{{ $platform }}"></span></a></li>
                                    @endforeach
                                </ul>
                            </a>
                            <div class="content">
                                <div class="info">
                                    <h5><a class="link" href="{{ $agent['link'] }}">{{ $agent['name'] }}</a></h5>
                                    <p class="text-variant-1">{{ $agent['role'] }}</p>
                                </div>
                                <div class="box-icon">
                                    @if(!empty($agent['phone']))
                                        <a href="tel:{{ $agent['phone'] }}"><span class="icon icon-phone"></span></a>
                                    @else
                                        <span class="icon icon-phone"></span>
                                    @endif
                                    @if(!empty($agent['email']))
                                        <a href="mailto:{{ $agent['email'] }}"><span class="icon icon-mail"></span></a>
                                    @else
                                        <span class="icon icon-mail"></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-mb-1 text-center d-sm-none d-block"></div>
        </div>
        <p class="text-center desc body-2 text-variant-3">{{ $footerText }} <a href="{{ $footerLink }}" class="text-primary"> {{ $footerLinkText }}</a></p>
    </div>
</section>
<!-- End Agents -->
