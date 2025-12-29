<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Models\ContentNode;
use App\Models\Template;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function home()
    {
        // Check if home exists in content tree
        $homeNode = ContentNode::where('url_path', '/')->first();

        if ($homeNode) {
            return $this->renderNode($homeNode);
        }

        // Fallback to old home page
        $home = Home::firstOrFail();
        return view('frontend.home', compact('home'));
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
        if (!$template) {
            return $this->handleDynamicRoute($request, $templateSlug);
        }

        // Get all entries for this template
        $modelClass = "App\\Models\\{$template->model_class}";

        if (!class_exists($modelClass)) {
            abort(500, "Model class {$modelClass} not found");
        }

        // Paginate entries
        $query = $modelClass::query();

        // Use active scope if available, but allow admins/editors to see all
        if (method_exists($modelClass, 'scopeActive')) {
            if (!auth()->check() || !auth()->user()->canViewDrafts()) {
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
        $urlPath = '/' . ltrim($path, '/');

        // Find the content node by URL path with caching (30 minutes)
        $node = \Cache::remember("content_node.path.{$urlPath}", 1800, function () use ($urlPath) {
            return ContentNode::where('url_path', $urlPath)
                ->where('is_published', true)
                ->with(['template', 'parent'])
                ->first();
        });

        if (!$node) {
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
        if (!$template->is_public) {
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
                if (!auth()->check() || !auth()->user()->canViewDrafts()) {
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

        // PRIORITY 2: Handle different render modes - check entry first, then template, then default
        $renderMode = ($content && isset($content->render_mode))
            ? $content->render_mode
            : ($template->render_mode ?? 'full_page_grapejs');

        switch ($renderMode) {
            case 'sections':
                // Render using page sections
                if ($content && method_exists($content, 'activeSections')) {
                    $data['sections'] = $content->activeSections()->get();
                }
                return view('frontend.sections', $data);

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
                        \Log::warning('Blade compilation failed for simple_content: ' . $e->getMessage());
                        $data['html'] = $rawHtml;
                    }
                }
                return view('frontend.simple', $data);

            case 'full_page_grapejs':
            default:
                // Full page GrapeJS or custom template
                // Note: Physical file checking is now done at PRIORITY 1 above

                // Final fallback: use default view with raw HTML
                // Compile Blade syntax if content has HTML
                if ($content && isset($content->html) && !empty($content->html)) {
                    try {
                        $data['content']->html = \Illuminate\Support\Facades\Blade::render($content->html, $data);
                    } catch (\Exception $e) {
                        \Log::warning('Blade compilation failed for GrapeJS content: ' . $e->getMessage());
                        // Keep original HTML if compilation fails
                    }
                }
                return view('frontend.default', $data);
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

        return 'frontend.templates.' . $template->slug;
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
        return 'frontend.templates.' . $template->slug;
    }

}
