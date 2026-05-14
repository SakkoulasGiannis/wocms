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

    // Template-design mode: when a section is owned by App\Models\Template (i.e. it
    // belongs to the SHARED layout of a template) AND we have an entry in the
    // current view context, resolve {field_name} tokens against that entry.
    //
    // The resolver is a no-op for sections owned by pages/properties/etc. that
    // don't have $entry in context — backward compatible with every existing page.
    $entryContext = $entry ?? $content ?? null;
    if (! is_object($entryContext)) {
        $entryContext = null;
    }
    if ($entryContext !== null) {
        try {
            $resolver = app(\App\Services\TokenResolver::class);
            $sectionContent  = $resolver->resolve($sectionContent,  $entryContext);
            $sectionSettings = $resolver->resolve($sectionSettings, $entryContext);
        } catch (\Throwable $e) {
            // Resolver should never throw; if it does, fall back to raw content.
            \Log::warning('TokenResolver failed: ' . $e->getMessage());
        }
    }

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
        // Resolve the current entry once so nested children see it too.
        $__childEntry = $entry ?? $content ?? null;
        foreach ($childSections as $childSection) {
            $childrenHtml .= view('pagebuilder::partials.render-section', [
                'section' => $childSection,
                'forceVe' => $forceVe ?? false,
                'entry'   => $__childEntry,
                'content' => $__childEntry,
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

{{-- 2–5: All rendering is done via isolated view()->render() to prevent
       exceptions from corrupting the parent Blade section stack --}}
@elseif($section->sectionTemplate?->blade_file && view()->exists($section->sectionTemplate->blade_file))
    @php
        try {
            echo view($section->sectionTemplate->blade_file, [
                'section'  => $section,
                'content'  => $sectionContent,
                'settings' => $sectionSettings,
            ])->render();
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                echo '<div style="background:#fee;border:1px solid #c00;color:#c00;padding:12px;margin:8px;border-radius:4px;">'
                    . '<strong>' . e($section->section_type) . ' (blade_file):</strong> ' . e($e->getMessage()) . '</div>';
            }
        }
    @endphp

@elseif(view()->exists('frontend.sections.' . $section->section_type))
    @php
        try {
            echo view('frontend.sections.' . $section->section_type, ['section' => $section])->render();
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                echo '<div style="background:#fee;border:1px solid #c00;color:#c00;padding:12px;margin:8px;border-radius:4px;">'
                    . '<strong>' . e($section->section_type) . ' (legacy):</strong> ' . e($e->getMessage()) . '</div>';
            }
        }
    @endphp

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
                    . '<strong>' . e($section->section_type) . ':</strong> ' . e($e->getMessage()) . '</div>';
            }
        }
    @endphp

@elseif($section->sectionTemplate?->html_template)
    {!! $section->sectionTemplate->render($sectionContent) !!}

@elseif(config('app.debug'))
    <div style="background:#ffc;border:1px solid #990;color:#660;padding:12px;margin:8px;border-radius:4px;">
        Section type <strong>{{ $section->section_type }}</strong> has no renderer.
    </div>
@endif

@if($isVeMode)</div>@endif
@endif{{-- end hidden check --}}
