@props([
    // Optional: pass a ContentNode explicitly. Otherwise the bar tries to detect
    // the current page context from the view's shared variables.
    'node' => null,
    'content' => null,
])

@php
    // Show only to logged-in admins / editors
    $user = auth()->user();
    $canShow = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'editor']);
@endphp
@if($canShow)
@php
    // Resolve ContentNode + edit URL from explicit props or shared view variables.
    $resolvedNode = $node;
    if (! $resolvedNode && isset($__data['node']) && $__data['node'] instanceof \App\Models\ContentNode) {
        $resolvedNode = $__data['node'];
    }
    $resolvedContent = $content;
    if (! $resolvedContent && isset($__data['content'])) {
        $resolvedContent = $__data['content'];
    }

    // Fallback: look up the ContentNode by the current request URL path. This catches
    // pages whose controller passes shared vars under a different name (e.g. $home,
    // $property) instead of $node, plus arbitrary "page" content nodes.
    if (! $resolvedNode) {
        try {
            $currentPath = '/' . trim(request()->path(), '/');
            $resolvedNode = \App\Models\ContentNode::where('url_path', $currentPath)
                ->with('template')
                ->first();
            if (! $resolvedNode && $currentPath === '/') {
                // Homepage often stored with empty url_path or as the root page
                $resolvedNode = \App\Models\ContentNode::whereIn('url_path', ['', '/', 'home'])
                    ->with('template')
                    ->first();
            }
        } catch (\Throwable $e) {}
    }

    // Compute "Edit this page" link
    $editUrl = null;
    $editLabel = 'Edit this page';
    try {
        if ($resolvedNode && $resolvedNode->template) {
            $templateSlug = $resolvedNode->template->slug;
            // The generic admin route: /admin/{templateSlug}/{entryId}/edit
            // entryId is the CONTENT MODEL id (e.g. Page id, CompletedVilla id) — NOT
            // the ContentNode id. The EntryForm does $modelClass::findOrFail($entryId).
            $entryId = $resolvedNode->content_id ?? $resolvedNode->id;
            $editUrl = route('admin.template-entries.edit', [
                'templateSlug' => $templateSlug,
                'entryId' => $entryId,
            ]);
            $editLabel = 'Edit ' . ($resolvedNode->template->name ?? 'page');
        }
    } catch (\Throwable $e) {
        $editUrl = null;
    }

    // Fallback paths for specific content types when no node is available
    if (! $editUrl) {
        // Single property view
        $property = $__data['property'] ?? null;
        if ($property && isset($property->id)) {
            try {
                if (str_contains(get_class($property), 'RentalProperty')) {
                    $editUrl = route('admin.rentals.edit', ['propertyId' => $property->id]);
                    $editLabel = 'Edit rental property';
                } else {
                    $editUrl = route('admin.properties.edit', ['propertyId' => $property->id]);
                    $editLabel = 'Edit property';
                }
            } catch (\Throwable $e) {}
        }

        // Single rental property (different var name)
        if (! $editUrl) {
            $rental = $__data['rentalProperty'] ?? null;
            if ($rental && isset($rental->id)) {
                try {
                    $editUrl = route('admin.rentals.edit', ['propertyId' => $rental->id]);
                    $editLabel = 'Edit rental property';
                } catch (\Throwable $e) {}
            }
        }

        // Single blog post / generic template entry (passed as $content alone)
        if (! $editUrl && $resolvedContent && isset($resolvedContent->id)) {
            // Try to map the model class to a template slug
            try {
                $modelClass = get_class($resolvedContent);
                $tpl = \App\Models\Template::query()->get()->first(function ($t) use ($modelClass) {
                    return ! empty($t->model_class)
                        && (ltrim($t->model_class, '\\') === ltrim($modelClass, '\\')
                            || str_ends_with($modelClass, '\\' . $t->model_class));
                });
                if ($tpl) {
                    // entryId = the content model id (Page id, Blog id, etc.)
                    $editUrl = route('admin.template-entries.edit', [
                        'templateSlug' => $tpl->slug,
                        'entryId' => $resolvedContent->id,
                    ]);
                    $editLabel = 'Edit ' . ($tpl->name ?: 'entry');
                }
            } catch (\Throwable $e) {}
        }

        // Template INDEX page (e.g. /completed-villas, /properties) — link to admin list
        if (! $editUrl) {
            try {
                $firstSegment = explode('/', trim(request()->path(), '/'))[0] ?? '';
                if ($firstSegment !== '') {
                    $tpl = \App\Models\Template::where('slug', $firstSegment)->where('use_slug_prefix', true)->first();
                    if ($tpl) {
                        $editUrl = route('admin.template-entries.index', ['templateSlug' => $tpl->slug]);
                        $editLabel = 'Manage ' . $tpl->name;
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    // Manage sections link — if the node has page sections enabled
    $manageSectionsUrl = null;
    if ($resolvedContent) {
        try {
            $renderMode = $resolvedContent->render_mode ?? ($resolvedNode?->template->render_mode ?? null);
            if ($renderMode === 'sections') {
                $modelClass = str_replace('\\', '-', get_class($resolvedContent));
                $manageSectionsUrl = url('/admin/page-sections/visual/' . $modelClass . '/' . $resolvedContent->id);
            }
        } catch (\Throwable $e) {}
    }

    $dashboardUrl = url('/admin');
    // Logout route name varies between auth scaffolds — try the common ones, fall back to URL
    $logoutUrl = null;
    foreach (['logout', 'filament.logout', 'admin.logout'] as $name) {
        try { $logoutUrl = route($name); break; } catch (\Throwable $e) {}
    }
    $logoutUrl = $logoutUrl ?? url('/logout');

    // Quick admin shortcuts
    $shortcuts = [];
    try { $shortcuts['Pages'] = route('admin.template-entries.index', ['templateSlug' => 'page']); } catch (\Throwable $e) {}
    try { $shortcuts['Properties'] = route('admin.properties.index'); } catch (\Throwable $e) {}
    try { $shortcuts['Media'] = url('/admin/media'); } catch (\Throwable $e) {}
@endphp

{{-- Admin top bar — fixed to viewport top, ~38px high. Push body content down with padding. --}}
<style>
    body { padding-top: 38px !important; }
    /* Push sticky frontend headers (sticky top-0) below the admin bar */
    body > header.sticky,
    body > * > header.sticky,
    body header.sticky.top-0 { top: 38px !important; }
    #admin-bar { position: fixed; top: 0; left: 0; right: 0; height: 38px; background: #161e2d; color: #f8fafc; font-size: 13px; z-index: 99998; box-shadow: 0 1px 0 rgba(0,0,0,.15); font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; }
    #admin-bar .ab-wrap { max-width: 100%; height: 100%; padding: 0 14px; display: flex; align-items: stretch; gap: 0; }
    #admin-bar .ab-item { display: flex; align-items: center; gap: 6px; padding: 0 12px; color: #e2e8f0; text-decoration: none; font-weight: 500; transition: background .15s ease, color .15s ease; cursor: pointer; }
    #admin-bar .ab-item:hover { background: #1d2738; color: #fff; }
    #admin-bar .ab-item.ab-brand { font-weight: 700; color: #fff; padding-left: 4px; }
    #admin-bar .ab-item.ab-edit { background: #1563df; color: #fff; }
    #admin-bar .ab-item.ab-edit:hover { background: #0e49a6; }
    #admin-bar .ab-spacer { flex: 1; }
    #admin-bar svg { width: 14px; height: 14px; flex-shrink: 0; }
    #admin-bar .ab-divider { width: 1px; background: rgba(255,255,255,.08); margin: 6px 0; }
    #admin-bar .ab-dropdown { position: relative; }
    #admin-bar .ab-menu { position: absolute; top: 100%; right: 0; min-width: 200px; background: #fff; color: #161e2d; border-radius: 6px; box-shadow: 0 8px 24px rgba(0,0,0,.18); padding: 4px; margin-top: 2px; }
    #admin-bar .ab-menu a, #admin-bar .ab-menu button { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 4px; color: #334155; text-decoration: none; font-size: 13px; background: none; border: 0; width: 100%; text-align: left; cursor: pointer; }
    #admin-bar .ab-menu a:hover, #admin-bar .ab-menu button:hover { background: #f1f5f9; color: #0f172a; }
    @media (max-width: 640px) {
        #admin-bar .ab-shortcuts, #admin-bar .ab-user-name { display: none; }
    }
    @media print {
        body { padding-top: 0 !important; }
        #admin-bar { display: none !important; }
    }
</style>

<div id="admin-bar" x-data="{ userOpen: false, addOpen: false }" @click.outside="userOpen = false; addOpen = false">
    <div class="ab-wrap">
        {{-- Brand / dashboard --}}
        <a href="{{ $dashboardUrl }}" class="ab-item ab-brand" title="Admin dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12L12 3l9 9M5 10v10a1 1 0 001 1h12a1 1 0 001-1V10"/></svg>
            <span>Admin</span>
        </a>

        <div class="ab-divider"></div>

        {{-- Primary edit action --}}
        @if($editUrl)
            <a href="{{ $editUrl }}" class="ab-item ab-edit" title="Edit current page in admin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <span>{{ $editLabel }}</span>
            </a>
        @endif

        {{-- Manage sections (if applicable) --}}
        @if($manageSectionsUrl)
            <a href="{{ $manageSectionsUrl }}" class="ab-item" title="Manage page sections">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Sections</span>
            </a>
        @endif

        {{-- Quick "New" dropdown --}}
        <div class="ab-dropdown">
            <button type="button" class="ab-item" @click.stop="addOpen = !addOpen" title="Quick create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16M4 12h16"/></svg>
                <span>New</span>
            </button>
            <div class="ab-menu" x-show="addOpen" x-transition style="display:none">
                @php
                    $quickAdd = [];
                    try { $quickAdd['New Page'] = route('admin.template-entries.create', ['templateSlug' => 'page']); } catch (\Throwable $e) {}
                    try { $quickAdd['New Blog Post'] = route('admin.template-entries.create', ['templateSlug' => 'blog']); } catch (\Throwable $e) {}
                    try { $quickAdd['New Property'] = route('admin.properties.index'); } catch (\Throwable $e) {}
                @endphp
                @foreach($quickAdd as $label => $href)
                    <a href="{{ $href }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="ab-spacer"></div>

        {{-- Shortcuts (hidden on mobile) --}}
        <div class="ab-shortcuts" style="display:flex">
            @foreach($shortcuts as $label => $href)
                <a href="{{ $href }}" class="ab-item">{{ $label }}</a>
            @endforeach
        </div>

        <div class="ab-divider"></div>

        {{-- User menu --}}
        <div class="ab-dropdown">
            <button type="button" class="ab-item" @click.stop="userOpen = !userOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="ab-user-name">{{ $user->name }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="ab-menu" x-show="userOpen" x-transition style="display:none">
                <a href="{{ $dashboardUrl }}">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="4" height="4" rx="1"/><rect x="8" y="2" width="4" height="4" rx="1"/><rect x="2" y="8" width="4" height="4" rx="1"/><rect x="8" y="8" width="4" height="4" rx="1"/></svg>
                    Dashboard
                </a>
                <form method="POST" action="{{ $logoutUrl }}" style="margin:0">
                    @csrf
                    <button type="submit">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 2H3a1 1 0 00-1 1v8a1 1 0 001 1h2M9 4l3 3-3 3M12 7H5"/></svg>
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
