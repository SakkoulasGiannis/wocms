<?php

namespace App\Services;

use App\Models\ContentNode;
use Illuminate\Support\Facades\View;

/**
 * Resolves the frontend layout (page chrome) for a ContentNode by walking
 * up the tree, then maps the resulting slug to a Blade view name.
 *
 * Resolution order:
 *   1. The node's own `layout` slug.
 *   2. The nearest ancestor (via parent_id) that has a non-null `layout`.
 *      → this is the "inherit from parent root" behaviour: set the layout
 *        once on a section root (e.g. /portal) and every descendant gets it.
 *   3. The site default (config('layouts.default')).
 *
 * The final slug is mapped to a view via config/layouts.php. If the slug
 * is unknown OR the Blade view doesn't exist, it degrades to the default
 * layout — so a typo or a half-configured layout never 500s the frontend.
 */
class LayoutResolver
{
    /**
     * Resolve the layout VIEW NAME for a node (e.g. 'layout',
     * 'layouts.minimal'), ready to hand to ThemeManager::getLayout().
     */
    public function resolveView(?ContentNode $node): string
    {
        $slug = $this->resolveSlug($node);

        return $this->slugToView($slug);
    }

    /**
     * Resolve the layout SLUG for a node, walking ancestors. Returns the
     * configured default when nothing is set anywhere up the chain.
     */
    public function resolveSlug(?ContentNode $node): string
    {
        $default = (string) config('layouts.default', 'default');

        if (! $node) {
            return $default;
        }

        // Walk up the ancestry: own layout first, then each parent.
        // Bounded loop guards against accidental cycles in parent_id.
        $current = $node;
        $guard = 0;
        while ($current && $guard < 50) {
            if (! empty($current->layout)) {
                return $current->layout;
            }
            $current = $current->parent_id ? $current->parent : null;
            $guard++;
        }

        return $default;
    }

    /**
     * Map a layout slug to a Blade view name via config, with a safety net:
     * unknown slug or missing view → default layout's view (or 'layout').
     */
    public function slugToView(string $slug): string
    {
        $layouts = (array) config('layouts.layouts', []);
        $defaultSlug = (string) config('layouts.default', 'default');

        $view = $layouts[$slug]['view'] ?? ($layouts[$defaultSlug]['view'] ?? 'layout');

        // Guard: if the chosen view doesn't resolve to a real Blade file in
        // the active theme, fall back to the always-present default. We
        // check via ThemeManager so theme/fallback resolution is honoured.
        $tm = app(ThemeManager::class);
        $resolved = $tm->getLayout($view);
        if (! View::exists($resolved)) {
            $fallbackView = $layouts[$defaultSlug]['view'] ?? 'layout';

            return $fallbackView;
        }

        return $view;
    }
}
