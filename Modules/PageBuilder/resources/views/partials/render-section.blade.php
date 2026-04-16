{{--
    Renders a single PageSection model.
    Usage: @include('partials.render-section', ['section' => $section])
--}}
@php
    $sectionContent  = is_array($section->content)  ? $section->content  : [];
    $sectionSettings = is_array($section->settings) ? $section->settings : [];
    $sectionTypeSlug = str_replace('_', '-', $section->section_type);
    $isVeMode        = ($forceVe ?? false) || request()->has('ve');
    $isHidden        = isset($section->is_visible) && ! $section->is_visible;

    // Build children HTML for container sections (primitive-div, primitive-grid, primitive-section)
    $childrenHtml = '';
    if ($section->relationLoaded('childrenRecursive')) {
        $childSections = $section->childrenRecursive;
    } elseif ($section->relationLoaded('children')) {
        $childSections = $section->children;
    } else {
        $childSections = $section->children()->with(['sectionTemplate', 'childrenRecursive.sectionTemplate'])->orderBy('order')->get();
    }
    if ($childSections->isNotEmpty()) {
        foreach ($childSections as $childSection) {
            $childrenHtml .= view('pagebuilder::partials.render-section', [
                'section' => $childSection,
                'forceVe' => $forceVe ?? false,
            ])->render();
        }
    }

    // Inject children into content so {{children}} is replaced in html_template
    if ($childrenHtml !== '') {
        $sectionContent['children'] = $childrenHtml;
    }
@endphp

@if(!$isVeMode && $isHidden){{-- Hidden section: skip in production --}}@else
@if($isVeMode)<div class="ve-section-wrapper{{ $isHidden ? ' ve-hidden' : '' }}"
    data-ve-section-id="{{ $section->id }}"
    data-ve-label="{{ $section->name ?: $section->section_type }}"
    style="display:contents">@endif

{{-- 1. Pre-rendered / cached HTML --}}
@if(!empty($section->rendered_html))
    {!! $section->rendered_html !!}

{{-- 2. Dedicated blade_file on the SectionTemplate --}}
@elseif($section->sectionTemplate?->blade_file && view()->exists($section->sectionTemplate->blade_file))
    @include($section->sectionTemplate->blade_file, [
        'section'  => $section,
        'content'  => $sectionContent,
        'settings' => $sectionSettings,
    ])

{{-- 3. Legacy partial: frontend/sections/{type}.blade.php --}}
@elseif(view()->exists('frontend.sections.' . $section->section_type))
    @include('frontend.sections.' . $section->section_type, ['section' => $section])

{{-- 4. View component: components/sections/{type}.blade.php --}}
@elseif(view()->exists('components.sections.' . $sectionTypeSlug))
    @php
        try {
            echo view('components.sections.' . $sectionTypeSlug, [
                'content'  => $sectionContent,
                'settings' => $sectionSettings,
            ])->render();
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                echo '<div style="background:#fee;border:1px solid #c00;color:#c00;padding:12px;margin:8px;border-radius:4px;">'
                    . '<strong>' . e($section->section_type) . ':</strong> ' . e($e->getMessage())
                    . '</div>';
            }
        }
    @endphp

{{-- 5. Inline html_template from SectionTemplate (primitives use this) --}}
@elseif($section->sectionTemplate?->html_template)
    {!! $section->sectionTemplate->render($sectionContent) !!}

{{-- 6. Debug fallback --}}
@elseif(config('app.debug'))
    <div style="background:#ffc;border:1px solid #990;color:#660;padding:12px;margin:8px;border-radius:4px;">
        Section type <strong>{{ $section->section_type }}</strong> has no renderer.
    </div>
@endif

@if($isVeMode)</div>@endif
@endif{{-- end hidden check --}}
