<?php

namespace App\Http\Controllers;

use App\Models\ContentNode;
use App\Models\Home;
use App\Models\Template;
use App\Services\ThemeManager;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    protected ThemeManager $themeManager;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * Resolve view: prefer theme override, fall back to default frontend view.
     * Example: themeView('properties.index') will try themes.{active}.templates.properties.index
     * and fall back to frontend.properties.index.
     */
    protected function themeView(string $view): string
    {
        $theme = $this->themeManager->getActiveTheme();
        $themed = "themes.{$theme}.templates.{$view}";

        return view()->exists($themed) ? $themed : "frontend.{$view}";
    }

    public function home()
    {
        // 1. Look for the explicitly marked default home page
        $homeTemplate = Template::where('slug', 'home')->first();

        if ($homeTemplate) {
            $homeNode = ContentNode::where('template_id', $homeTemplate->id)
                ->where('is_default', true)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->first();

            // Fallback: first published home when none is marked default
            $homeNode ??= ContentNode::where('template_id', $homeTemplate->id)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->first();

            if ($homeNode) {
                return $this->renderNode($homeNode);
            }
        }

        // 2. Legacy: node pinned to '/'
        $homeNode = ContentNode::where('url_path', '/')->first();

        if ($homeNode) {
            return $this->renderNode($homeNode);
        }

        // 3. Last resort fallback
        $home = Home::firstOrFail();

        return view('frontend.home', compact('home'));
    }

    /**
     * Contact page — prefer Contact ContentNode (sections), fallback to themed view.
     */
    public function contact()
    {
        $node = ContentNode::where('url_path', '/contact')
            ->where('is_published', true)
            ->whereNull('deleted_at')
            ->first();

        if ($node) {
            return $this->renderNode($node);
        }

        return view($this->themeView('contact'));
    }

    /**
     * Our Staff (Agents) page
     */
    public function staff()
    {
        $agents = \App\Models\Agent::query()
            ->active()
            ->ordered()
            ->get();

        return view($this->themeView('our-staff'), [
            'agents' => $agents,
            'title' => 'Our Staff',
        ]);
    }

    /**
     * Properties listing page with filters and map
     */
    public function properties(Request $request)
    {
        $query = \Modules\Properties\Models\Property::query()->active();

        // Filters
        if ($request->filled('type')) {
            $query->where('property_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->city.'%');
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }
        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', '>=', $request->bathrooms);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->latest(),
        };

        $properties = $query->paginate(12)->withQueryString();
        $propertyTypes = \Modules\Properties\Models\Property::getPropertyTypes();
        $statuses = \Modules\Properties\Models\Property::getStatuses();

        return view($this->themeView('properties.index'), [
            'properties' => $properties,
            'propertyTypes' => $propertyTypes,
            'statuses' => $statuses,
            'title' => 'Properties',
            'filters' => $request->only(['type', 'status', 'city', 'min_price', 'max_price', 'bedrooms', 'bathrooms', 'search', 'sort']),
        ]);
    }

    /**
     * Rental properties listing page
     */
    public function rentalProperties(Request $request)
    {
        $query = \Modules\RentalProperties\Models\RentalProperty::query()->active();

        if ($request->filled('type')) {
            $query->where('property_type', $request->type);
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->city.'%');
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }
        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', '>=', $request->bathrooms);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            // Only search columns that actually exist on this environment.
            // The rental_properties schema on production never ran the
            // "add address/state/country" migration, so blindly filtering
            // by `address` 500s the page.
            $searchable = array_values(array_filter(
                ['title', 'address', 'city', 'description'],
                fn ($col) => \Illuminate\Support\Facades\Schema::hasColumn('rental_properties', $col),
            ));
            if (! empty($searchable)) {
                $query->where(function ($q) use ($searchable, $search) {
                    foreach ($searchable as $i => $col) {
                        $i === 0
                            ? $q->where($col, 'like', "%{$search}%")
                            : $q->orWhere($col, 'like', "%{$search}%");
                    }
                });
            }
        }

        $sort = $request->get('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->latest(),
        };

        $properties = $query->paginate(12)->withQueryString();

        return view($this->themeView('rental-properties.index'), [
            'properties' => $properties,
            'propertyTypes' => \Modules\RentalProperties\Models\RentalProperty::getPropertyTypes(),
            'statuses' => \Modules\RentalProperties\Models\RentalProperty::getStatuses(),
            'title' => 'Rental Properties',
            'filters' => $request->only(['type', 'status', 'city', 'min_price', 'max_price', 'bedrooms', 'bathrooms', 'search', 'sort']),
        ]);
    }

    /**
     * Single rental property detail page
     */
    public function rentalPropertyShow(string $slug)
    {
        // Some rentals exist twice (a legacy manual record + the CRM-synced copy
        // sharing the same slug). Prefer the synced one (has hostaway_id) so the
        // detail page shows live data + the availability calendar.
        $property = \Modules\RentalProperties\Models\RentalProperty::where('slug', $slug)->active()
            ->orderByRaw('hostaway_id IS NULL')
            ->orderByDesc('external_id')
            ->firstOrFail();

        $related = \Modules\RentalProperties\Models\RentalProperty::active()
            ->where('id', '!=', $property->id)
            ->where('city', $property->city)
            ->limit(3)->get();

        return view($this->themeView('rental-properties.show'), [
            'property' => $property,
            'related' => $related,
            'title' => $property->title,
        ]);
    }

    /**
     * Availability calendar for a rental property, fetched live from Hostaway
     * (server-side + cached). Consumed asynchronously by the frontend widget.
     * Returns a 12-month window from the start of the current month.
     */
    public function rentalPropertyCalendar(string $slug): \Illuminate\Http\JsonResponse
    {
        $property = $this->resolveRental($slug);

        if (empty($property->hostaway_id)) {
            return response()->json(['success' => false, 'error' => 'no_hostaway_id', 'days' => []]);
        }

        $start = now()->startOfMonth()->toDateString();
        $end = now()->addMonths(11)->endOfMonth()->toDateString();

        $data = app(\Modules\RentalProperties\Services\HostawayClient::class)
            ->getCalendar((string) $property->hostaway_id, $start, $end);

        $data['listing'] = app(\Modules\RentalProperties\Services\BookingService::class)->listingInfo($property);
        $data['listing']['bookingEnabled'] = $this->bookingEnabled();

        return response()->json($data);
    }

    /**
     * Whether guests may submit booking requests. Off by default — toggled from
     * the admin (Settings → Hostaway) so the flow can be paused without a deploy.
     */
    protected function bookingEnabled(): bool
    {
        return filter_var(\App\Models\Setting::get('rental_booking_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Price quote for a date range + guest count (availability-validated).
     */
    public function rentalPropertyQuote(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $property = $this->resolveRental($slug);
        if (empty($property->hostaway_id)) {
            return response()->json(['ok' => false, 'errors' => ['Online booking is not available for this property.']]);
        }

        $validated = $request->validate([
            'checkin' => 'required|date',
            'checkout' => 'required|date',
            'adults' => 'nullable|integer|min:1|max:50',
            'children' => 'nullable|integer|min:0|max:50',
        ]);

        $quote = app(\Modules\RentalProperties\Services\BookingService::class)->quote(
            $property,
            $validated['checkin'],
            $validated['checkout'],
            (int) ($validated['adults'] ?? 1),
            (int) ($validated['children'] ?? 0),
        );

        return response()->json($quote);
    }

    /**
     * Request-to-book: re-validate, then create an inquiry reservation in Hostaway.
     */
    public function rentalPropertyBook(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $property = $this->resolveRental($slug);
        if (empty($property->hostaway_id)) {
            return response()->json(['success' => false, 'message' => 'Online booking is not available for this property.'], 422);
        }

        if (! $this->bookingEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Online booking requests are temporarily unavailable. Please contact us directly.',
            ], 403);
        }

        $validated = $request->validate([
            'checkin' => 'required|date',
            'checkout' => 'required|date',
            'adults' => 'nullable|integer|min:1|max:50',
            'children' => 'nullable|integer|min:0|max:50',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'nullable|string|max:40',
            'message' => 'nullable|string|max:1000',
        ]);

        $booking = app(\Modules\RentalProperties\Services\BookingService::class);

        // Re-quote server-side — never trust a price/availability sent by the client.
        $quote = $booking->quote(
            $property,
            $validated['checkin'],
            $validated['checkout'],
            (int) ($validated['adults'] ?? 1),
            (int) ($validated['children'] ?? 0),
        );

        if (empty($quote['ok'])) {
            return response()->json([
                'success' => false,
                'message' => $quote['errors'][0] ?? 'These dates are no longer available.',
            ], 422);
        }

        $payload = $booking->buildReservationPayload($property, $quote, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? '',
            'message' => $validated['message'] ?? '',
        ]);

        $result = app(\Modules\RentalProperties\Services\HostawayClient::class)->createReservation($payload);

        if (empty($result['success'])) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Your request could not be sent. Please try again.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your booking request has been sent. Our team will confirm availability and payment details shortly.',
            'reservationId' => $result['reservation']['id'] ?? null,
        ]);
    }

    /**
     * Resolve a rental by slug, preferring the CRM-synced copy (has hostaway_id)
     * when a legacy manual duplicate shares the same slug.
     */
    protected function resolveRental(string $slug): \Modules\RentalProperties\Models\RentalProperty
    {
        return \Modules\RentalProperties\Models\RentalProperty::where('slug', $slug)
            ->active()
            ->orderByRaw('hostaway_id IS NULL')
            ->orderByDesc('external_id')
            ->firstOrFail();
    }

    /**
     * Single property detail page
     */
    public function propertyShow(string $slug)
    {
        $property = \Modules\Properties\Models\Property::where('slug', $slug)->active()->firstOrFail();
        $property->increment('views');

        $related = \Modules\Properties\Models\Property::active()
            ->where('id', '!=', $property->id)
            ->where('property_type', $property->property_type)
            ->limit(3)->get();

        return view($this->themeView('properties.show'), [
            'property' => $property,
            'related' => $related,
            'title' => $property->title,
        ]);
    }

    /**
     * Handle template index pages (e.g., /services, /blog)
     */
    public function handleTemplateIndex(Request $request, $templateSlug)
    {
        // Find template by slug
        $template = Template::where('slug', $templateSlug)
            ->where('use_slug_prefix', true)
            ->where('is_public', true)
            ->where('is_active', true)
            ->first();

        // If no template with slug prefix found, pass to dynamic route
        if (! $template) {
            return $this->handleDynamicRoute($request, $templateSlug);
        }

        // Get all entries for this template
        $modelClass = "App\\Models\\{$template->model_class}";

        if (! class_exists($modelClass)) {
            abort(500, "Model class {$modelClass} not found");
        }

        // Paginate entries
        $query = $modelClass::query();

        // Use active scope if available, but allow admins/editors to see all
        if (method_exists($modelClass, 'scopeActive')) {
            if (! auth()->check() || ! auth()->user()->canViewDrafts()) {
                $query->active();
            }
        }

        // Respect the manual drag-ordering set in the admin entry list when the
        // template is sortable and the model's table has a sort_order column.
        // Falls back to newest-first for non-sortable templates (unchanged).
        $sortable = (bool) ($template->settings['sortable'] ?? false);
        $table = (new $modelClass)->getTable();

        if ($sortable && \Illuminate\Support\Facades\Schema::hasColumn($table, 'sort_order')) {
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->latest();
        }

        $entries = $query->paginate(12);

        // Prepare data
        $data = [
            'template' => $template,
            'entries' => $entries,
            'title' => $template->name,
        ];

        // TEMPLATE-DESIGN MODE — listing scope. If the TEMPLATE has 'listing' scoped
        // design sections (set via the "Design listing page" button), they take
        // precedence over the static blade file and the default frontend.index.
        // Sections (e.g. Entry Loop) get $entries available in scope for direct use.
        try {
            $listingSections = $template->listingSections()
                ->whereNull('parent_section_id')
                ->with(['sectionTemplate', 'childrenRecursive.sectionTemplate'])
                ->get();
            if ($listingSections->isNotEmpty()) {
                $data['sections'] = $listingSections;
                $data['__prerendered'] = $this->prerenderSections($listingSections, $data['entry'] ?? $data['content'] ?? null, request()->has('ve'));
                $view = $this->themeManager->getTemplateView('sections') ?? 'frontend.sections';

                return $this->sectionsResponse($view, $data);
            }
        } catch (\Throwable $e) {
            \Log::warning('Template listing-design section load failed for '.$template->slug.': '.$e->getMessage());
        }

        // Check if template has index view (plural)
        $indexViewPath = $this->getIndexViewPath($template);

        if (view()->exists($indexViewPath)) {
            return view($indexViewPath, $data);
        }

        // Fallback to default index view
        return view('frontend.index', $data);
    }

    /**
     * Handle all dynamic routes
     */
    public function handleDynamicRoute(Request $request, $path = null)
    {
        \Log::info("🔴 FrontendController::handleDynamicRoute() called for path: {$path}");

        // Build the full URL path
        $urlPath = '/'.ltrim($path, '/');

        // Find the content node by URL path with caching (30 minutes)
        $node = \Cache::remember("content_node.path.{$urlPath}", 1800, function () use ($urlPath) {
            return ContentNode::where('url_path', $urlPath)
                ->where('is_published', true)
                ->with(['template', 'parent'])
                ->first();
        });

        if (! $node) {
            abort(404, "Page not found: {$urlPath}");
        }

        return $this->renderNode($node);
    }

    /**
     * Render a content node with its template
     */
    /**
     * Pre-render page sections to an HTML string in the CONTROLLER (i.e. before
     * any Blade view render is active). Some sections render Livewire-backed
     * components whose rendering pops Blade's section stack; doing that inside
     * frontend.sections' own render corrupted the parent's @section('content')
     * and silently blanked the whole page. Rendering here — in a clean context —
     * sidesteps that entirely, and the view just echoes the result.
     *
     * @param  \Illuminate\Support\Collection  $sections
     */
    protected function prerenderSections($sections, $entry, bool $forceVe = false): string
    {
        $html = '';

        // Snapshot the View factory's Blade section state. Livewire-backed
        // sections pop the section stack as a side effect of rendering; left
        // unrestored, that corrupts the OUTER view's @section('content') and
        // blanks the whole page. We restore the snapshot afterwards.
        $vf = app('view');
        $pStack = null;
        $pSecs = null;
        $pCount = null;
        $snapStack = null;
        $snapSecs = null;
        $snapCount = null;
        try {
            $ro = new \ReflectionObject($vf);
            if ($ro->hasProperty('sectionStack')) {
                $pStack = $ro->getProperty('sectionStack');
                $pStack->setAccessible(true);
                $snapStack = $pStack->getValue($vf);
            }
            if ($ro->hasProperty('sections')) {
                $pSecs = $ro->getProperty('sections');
                $pSecs->setAccessible(true);
                $snapSecs = $pSecs->getValue($vf);
            }
            // The View factory's renderCount governs WHEN Blade flushes captured
            // sections (only at renderCount === 0). Section renders that throw or
            // contain Livewire components can leave it unbalanced (e.g. -2), which
            // makes the OUTER @section('content') flush inline — before the layout
            // header instead of into <main>. Snapshot it and restore afterwards.
            if ($ro->hasProperty('renderCount')) {
                $pCount = $ro->getProperty('renderCount');
                $pCount->setAccessible(true);
                $snapCount = $pCount->getValue($vf);
            }
        } catch (\Throwable $e) {
            $pStack = null;
            $pSecs = null;
            $pCount = null;
        }

        foreach ($sections as $section) {
            try {
                $html .= view('partials.render-section', [
                    'section' => $section,
                    'forceVe' => $forceVe,
                    'entry' => $entry,
                    'content' => $entry,
                ])->render();
            } catch (\Throwable $e) {
                \Log::warning('Section render failed #'.($section->id ?? '?').' ('.($section->section_type ?? '?').'): '.$e->getMessage());
            }
        }

        // Restore — undo any Blade section-stack corruption the section renders
        // (Livewire components) caused, so the outer @section('content') is clean.
        if ($pStack !== null && $snapStack !== null) {
            $pStack->setValue($vf, $snapStack);
        }
        if ($pSecs !== null && $snapSecs !== null) {
            $pSecs->setValue($vf, $snapSecs);
        }
        if ($pCount !== null && $snapCount !== null) {
            $pCount->setValue($vf, $snapCount);
        }

        return $html;
    }

    /**
     * Render a sections view to a Response, swapping the placeholder token for
     * the pre-rendered sections HTML. (Large HTML placed straight into a Blade
     *
     * @section is dropped by section capture; a short token survives and is
     * replaced here, after render.)
     */
    protected function sectionsResponse(string $viewName, array $data): \Illuminate\Http\Response
    {
        $html = view($viewName, $data)->render();
        $content = $data['__prerendered'] ?? '';
        if ($content === '') {
            $content = '<div class="container mx-auto px-4 py-12"><div class="text-center text-gray-500"><p>This page has no sections yet. Add sections from the admin panel.</p></div></div>';
        }
        $html = str_replace('__VE_PRERENDERED_SECTIONS__', $content, $html);

        return response($html);
    }

    protected function renderNode(ContentNode $node)
    {
        $template = $node->template;

        // Check if template is publicly accessible
        if (! $template->is_public) {
            abort(403, 'This content is not publicly accessible');
        }

        // Check if caching is enabled for this node
        if ($node->isCacheEnabled()) {
            $cacheKey = "page.{$node->url_path}";
            $cacheTtl = $node->getCacheTtl();

            // Check if cache exists
            if (\Cache::has($cacheKey)) {
                \Log::info("✅ CACHE HIT: {$node->url_path} (serving from cache)");

                return response(\Cache::get($cacheKey));
            }

            // Cache miss - generate content and cache it as HTML string
            \Log::info("❌ CACHE MISS: {$node->url_path} (generating and caching for {$cacheTtl}s)");
            $result = $this->renderNodeContent($node, $template);

            // Render to HTML string before caching. renderNodeContent may return
            // either a View (most modes) or a Response (sections mode, where the
            // pre-rendered HTML is swapped in post-render).
            $html = $result instanceof \Symfony\Component\HttpFoundation\Response
                ? $result->getContent()
                : $result->render();
            \Cache::put($cacheKey, $html, $cacheTtl);

            return $result;
        }

        \Log::info("🚫 NO CACHE: {$node->url_path} (caching disabled)");

        return $this->renderNodeContent($node, $template);
    }

    /**
     * Render the actual node content (called from renderNode, may be cached)
     */
    protected function renderNodeContent(ContentNode $node, $template)
    {
        // Resolve the page chrome (layout) for this node, walking up the
        // tree for inheritance, and make it the active layout for this
        // request. ThemeManager is a singleton, so every view's
        // @extends(...->getLayout()) picks it up with no per-view changes.
        // Nodes with no layout (the default) resolve to 'layout' = current
        // behaviour, so existing pages render identically.
        try {
            $layoutView = app(\App\Services\LayoutResolver::class)->resolveView($node);
            $this->themeManager->setActiveLayout($layoutView);
        } catch (\Throwable $e) {
            $this->themeManager->setActiveLayout(null); // safe default
        }

        // Get the content data if it exists
        $content = null;
        if ($node->content_type && $node->content_id) {
            $content = $node->getContentModel();

            // Check if content has status field and is not active
            // Allow admins and editors to view draft/disabled content
            if ($content && isset($content->status) && $content->status !== 'active') {
                if (! auth()->check() || ! auth()->user()->canViewDrafts()) {
                    abort(404, 'This content is not available');
                }
            }
        }

        // Prepare base data for the view
        $data = [
            'node' => $node,
            'template' => $template,
            'content' => $content,
            'entry' => $content,   // alias so section views + token resolver always have `$entry`
            'title' => $node->title,
            'breadcrumbs' => $node->breadcrumbs(),
        ];

        // Load sections if template or content uses sections render mode
        $renderMode = ($content && isset($content->render_mode))
            ? $content->render_mode
            : ($template->render_mode ?? 'full_page_grapejs');

        if ($renderMode === 'sections' && $content && method_exists($content, 'activeSections')) {
            $data['sections'] = $content->activeSections()
                ->whereNull('parent_section_id')
                ->with(['sectionTemplate', 'childrenRecursive.sectionTemplate'])
                ->get();
            $data['__prerendered'] = $this->prerenderSections($data['sections'], $content, request()->has('ve'));
        }

        // TEMPLATE-DESIGN MODE — single-entry pages. If the TEMPLATE has 'entry'
        // scoped design sections, they take precedence over the static blade file.
        // Every entry of this template renders with the same shared design; tokens like
        // {name}, {main_image:hero} resolve against the current $content (entry).
        if (empty($data['sections']) && $template) {
            try {
                $tplSections = $template->entrySections()
                    ->whereNull('parent_section_id')
                    ->with(['sectionTemplate', 'childrenRecursive.sectionTemplate'])
                    ->get();
                if ($tplSections->isNotEmpty()) {
                    $data['sections'] = $tplSections;
                    $data['__prerendered'] = $this->prerenderSections($tplSections, $content, request()->has('ve'));
                    $view = $this->themeManager->getTemplateView('sections') ?? 'frontend.sections';

                    return $this->sectionsResponse($view, $data);
                }
            } catch (\Throwable $e) {
                \Log::warning('Template entry-design section load failed for '.$template->slug.': '.$e->getMessage());
            }
        }

        // PRIORITY 1: Check if there's a physical blade file for this template
        if ($template->has_physical_file) {
            // First, check if there's a generated blade file for this specific entry
            if ($content) {
                $slug = $content->slug ?? $content->id;
                $entryViewPath = "frontend.templates.{$template->slug}-{$slug}";

                if (view()->exists($entryViewPath)) {
                    return view($entryViewPath, $data);
                }
            }

            // Check if template has a general physical file
            $viewPath = $this->getViewPath($template);
            if (view()->exists($viewPath)) {
                return view($viewPath, $data);
            }
        }

        // PRIORITY 2: Handle different render modes - use template render_mode
        $renderMode = $template->render_mode ?? 'full_page_grapejs';

        // Allow per-entry override if the entry has render_mode field
        if ($content && isset($content->render_mode)) {
            $renderMode = $content->render_mode;
        }

        switch ($renderMode) {
            case 'sections':
                // Render using page sections (eager load sectionTemplate + nested children)
                if ($content && method_exists($content, 'activeSections') && ! isset($data['sections'])) {
                    $data['sections'] = $content->activeSections()
                        ->whereNull('parent_section_id')
                        ->with(['sectionTemplate', 'childrenRecursive.sectionTemplate'])
                        ->get();
                }
                if (isset($data['sections']) && ! isset($data['__prerendered'])) {
                    $data['__prerendered'] = $this->prerenderSections($data['sections'], $content, request()->has('ve'));
                }
                $view = $this->themeManager->getTemplateView('sections') ?? 'frontend.sections';

                return $this->sectionsResponse($view, $data);

            case 'simple_content':
                // Simple WYSIWYG content
                if ($content) {
                    // Find the first wysiwyg/textarea field in the template
                    $contentField = $template->fields()
                        ->whereIn('type', ['wysiwyg', 'textarea', 'grapejs'])
                        ->orderBy('order')
                        ->first();

                    if ($contentField) {
                        $rawHtml = $content->{$contentField->name} ?? '';
                    } else {
                        // Fallback to 'content' field
                        $rawHtml = $content->content ?? '';
                    }

                    // Compile Blade syntax in the HTML
                    try {
                        $data['html'] = \Illuminate\Support\Facades\Blade::render($rawHtml, $data);
                    } catch (\Exception $e) {
                        // If Blade compilation fails, use raw HTML
                        \Log::warning('Blade compilation failed for simple_content: '.$e->getMessage());
                        $data['html'] = $rawHtml;
                    }
                }
                $view = $this->themeManager->getTemplateView('simple') ?? 'frontend.simple';

                return view($view, $data);

            case 'full_page_grapejs':
            default:
                // Full page GrapeJS or custom template
                // Note: Physical file checking is now done at PRIORITY 1 above

                // Final fallback: use default view with raw HTML
                // Compile Blade syntax if content has HTML
                if ($content && isset($content->html) && ! empty($content->html)) {
                    try {
                        $data['content']->html = \Illuminate\Support\Facades\Blade::render($content->html, $data);
                    } catch (\Exception $e) {
                        \Log::warning('Blade compilation failed for GrapeJS content: '.$e->getMessage());
                        // Keep original HTML if compilation fails
                    }
                }
                $view = $this->themeManager->getTemplateView('default') ?? 'frontend.default';

                return view($view, $data);
        }
    }

    /**
     * Get the view path for a template index (plural, e.g., /services)
     */
    protected function getIndexViewPath(Template $template): string
    {
        if ($template->file_path && $template->use_slug_prefix) {
            // For slug-prefixed templates, use the plural form
            $path = str_replace('.blade.php', '', $template->file_path);
            $path = str_replace('/', '.', $path);

            return $path; // e.g., "templates.services"
        }

        return 'frontend.templates.'.$template->slug;
    }

    /**
     * Get the view path for a single template entry (singular, e.g., /services/web-design)
     */
    protected function getViewPath(Template $template): string
    {
        if ($template->file_path && $template->use_slug_prefix) {
            // For slug-prefixed templates, use the singular form
            $path = str_replace('.blade.php', '', $template->file_path);
            $path = str_replace('/', '.', $path);

            // Convert plural to singular (remove trailing 's')
            // templates.services -> templates.service
            $pathParts = explode('.', $path);
            $lastPart = array_pop($pathParts);

            // Simple pluralization removal (works for most cases)
            if (substr($lastPart, -1) === 's') {
                $lastPart = substr($lastPart, 0, -1);
            }

            $pathParts[] = $lastPart;

            return implode('.', $pathParts);
        }

        if ($template->file_path) {
            $path = str_replace('.blade.php', '', $template->file_path);
            $path = str_replace('/', '.', $path);

            return $path;
        }

        // Default to templates.{slug}
        return 'frontend.templates.'.$template->slug;
    }
}
