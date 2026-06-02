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
@php
    ob_start();
    // A primitive div/section with NO class and NO id is a purely structural
    // wrapper. Rendering it as a normal <div> inserts an extra block box that
    // breaks the parent's grid/flex layout (e.g. a child's col-span-full stops
    // working because it's no longer a direct grid item). Mark it so we can
    // make it layout-transparent with display:contents.
    $__isStructuralWrapper = false;
    if (in_array($section->section_type, ['primitive_div', 'primitive_section'], true)) {
        $__cls = trim((string) ($sectionContent['class'] ?? ''));
        $__pid = trim((string) ($sectionContent['id'] ?? ''));
        $__isStructuralWrapper = ($__cls === '' && $__pid === '');
    }
@endphp

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
                'entry'    => $entryContext,
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
            // Legacy view path — historically only received \$section and re-read
            // \$section->content itself, bypassing TokenResolver. We now pass the
            // RESOLVED \$sectionContent/\$sectionSettings as well so legacy views
            // that already read from those vars get tokens substituted. Views that
            // still re-read \$section->content directly will need a small update.
            echo view('frontend.sections.' . $section->section_type, [
                'section'  => $section,
                'content'  => $sectionContent,
                'settings' => $sectionSettings,
                'entry'    => $entryContext,
            ])->render();
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
                'entry'    => $entryContext,
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

@php
    $__html = ob_get_clean();

    // Transform the section's FIRST element in a single pass:
    //  • structural wrapper (no class/no id)  → add style="display:contents"
    //    so it doesn't create a box that breaks the ancestor grid/flex layout
    //  • ve mode → add data-ve-section-id / data-ve-label / .ve-section so the
    //    editor overlay can find & highlight the section (no wrapper div)
    if ($__isStructuralWrapper || $isVeMode) {
        $__veClass = 've-section' . ($isHidden ? ' ve-hidden' : '');
        $__veData  = ' data-ve-section-id="' . $section->id . '"'
                   . ' data-ve-label="' . e($section->name ?: $section->section_type) . '"';
        $__done = false;
        $__html = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9-]*)((?:\s[^>]*?)?)(\/?)>/s', function ($m) use ($__veData, $__veClass, $isVeMode, $__isStructuralWrapper, &$__done) {
            if ($__done) {
                return $m[0];
            }
            $__done = true;
            $tag = $m[1];
            $rest = $m[2];
            $selfClose = $m[3];

            if ($__isStructuralWrapper) {
                // Merge into an existing style attr or add one. display:contents
                // removes the box; children join the ancestor's grid/flex flow.
                if (preg_match('/\sstyle="([^"]*)"/i', $rest)) {
                    $rest = preg_replace('/\sstyle="([^"]*)"/i', ' style="$1;display:contents"', $rest, 1);
                } else {
                    $rest .= ' style="display:contents"';
                }
            }

            if ($isVeMode) {
                if (preg_match('/\sclass="([^"]*)"/i', $rest)) {
                    $rest = preg_replace('/\sclass="([^"]*)"/i', ' class="$1 ' . $__veClass . '"', $rest, 1);
                } else {
                    $rest .= ' class="' . $__veClass . '"';
                }
                return '<' . $tag . $__veData . $rest . $selfClose . '>';
            }

            return '<' . $tag . $rest . $selfClose . '>';
        }, $__html, 1);
    }

    echo $__html;
@endphp
@endif{{-- end hidden check --}}
