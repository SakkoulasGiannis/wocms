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
     * Contact page
     */
    public function contact()
    {
        return view('frontend.contact');
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

        return view('frontend.properties.index', [
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

        return view('frontend.rental-properties.index', [
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
        $property = \Modules\RentalProperties\Models\RentalProperty::where('slug', $slug)->active()->firstOrFail();

        $related = \Modules\RentalProperties\Models\RentalProperty::active()
            ->where('id', '!=', $property->id)
            ->where('city', $property->city)
            ->limit(3)->get();

        return view('frontend.rental-properties.show', [
            'property' => $property,
            'related' => $related,
            'title' => $property->title,
        ]);
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

        return view('frontend.properties.show', [
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

        $entries = $query->latest()->paginate(12);

        // Prepare data
        $data = [
            'template' => $template,
            'entries' => $entries,
            'title' => $template->name,
        ];

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
            $view = $this->renderNodeContent($node, $template);

            // Render view to HTML string before caching
            $html = $view->render();
            \Cache::put($cacheKey, $html, $cacheTtl);

            return $view;
        }

        \Log::info("🚫 NO CACHE: {$node->url_path} (caching disabled)");

        return $this->renderNodeContent($node, $template);
    }

    /**
     * Render the actual node content (called from renderNode, may be cached)
     */
    protected function renderNodeContent(ContentNode $node, $template)
    {

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
            \Log::info('📋 Loaded sections for page', [
                'url' => $node->url_path,
                'sections_count' => $data['sections']->count(),
                'sections' => $data['sections']->pluck('name', 'id')->toArray(),
            ]);
        } else {
            \Log::info('⚠️ No sections loaded', [
                'url' => $node->url_path,
                'render_mode' => $renderMode,
                'has_content' => $content !== null,
                'has_activeSections_method' => $content ? method_exists($content, 'activeSections') : false,
            ]);
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
                $view = $this->themeManager->getTemplateView('sections') ?? 'frontend.sections';

                return view($view, $data);

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
