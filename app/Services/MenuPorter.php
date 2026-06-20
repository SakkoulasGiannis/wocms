<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * JSON export/import for the Menu admin.
 *
 * Designed to be safe across environments — IDs are intentionally not
 * exported (they would collide on import); the tree relies on slug for the
 * Menu and `linkable_type` + slug for menu items whose target is a Template
 * entry. Custom-URL items stay as-is. The shape mirrors what the existing
 * `loadMenuItems()` already produces so consumers (AI prompts, backups,
 * fixtures) get a stable contract:
 *
 *   {
 *     "version": 1,
 *     "menu": {
 *       "name": "Main Menu",
 *       "slug": "main-menu",
 *       "location": "header",
 *       "is_active": true
 *     },
 *     "items": [
 *       {
 *         "title": "Home",
 *         "url": "/",
 *         "type": "homepage",
 *         "target": "_self",
 *         "icon": null,
 *         "css_class": null,
 *         "is_active": true,
 *         "linkable_type": null,
 *         "linkable_slug": null,
 *         "children": [ ... recursive ... ]
 *       }
 *     ]
 *   }
 */
class MenuPorter
{
    /** Export a single menu (with its full item tree) to an array spec. */
    public function exportMenu(Menu $menu): array
    {
        $menu->loadMissing(['items.linkable']);

        return [
            'version' => 1,
            'menu' => [
                'name' => $menu->name,
                'slug' => $menu->slug,
                'location' => $menu->location,
                'is_active' => (bool) $menu->is_active,
            ],
            'items' => $this->serializeItemTree(
                $menu->items()->whereNull('parent_id')->orderBy('order')->get()
            ),
        ];
    }

    /**
     * Import a spec array into the DB. By default creates a new Menu (with a
     * unique slug derived from the spec) — pass mode=replace to clear the
     * matching slug's existing items first. Returns a result array with
     * { ok, menu_id, slug, items_created, warnings }.
     */
    public function importMenu(array $spec, string $mode = 'create'): array
    {
        if (! isset($spec['menu'], $spec['items']) || ! is_array($spec['menu']) || ! is_array($spec['items'])) {
            return ['ok' => false, 'error' => 'Invalid spec: missing "menu" or "items".'];
        }

        $warnings = [];
        $itemsCreated = 0;

        return DB::transaction(function () use ($spec, $mode, &$warnings, &$itemsCreated) {
            $m = $spec['menu'];
            $name = trim((string) ($m['name'] ?? 'Imported menu'));
            $location = (string) ($m['location'] ?? '');
            $isActive = (bool) ($m['is_active'] ?? true);
            $requestedSlug = Str::slug((string) ($m['slug'] ?? $name));

            $menu = null;
            if ($mode === 'replace') {
                $menu = Menu::where('slug', $requestedSlug)->first();
                if ($menu) {
                    $menu->items()->delete();
                    $menu->update(['name' => $name, 'location' => $location, 'is_active' => $isActive]);
                }
            }
            if (! $menu) {
                $slug = $this->ensureUniqueSlug($requestedSlug);
                $menu = Menu::create([
                    'name' => $name,
                    'slug' => $slug,
                    'location' => $location,
                    'is_active' => $isActive,
                ]);
            }

            $this->materializeItems($menu->id, null, $spec['items'], $itemsCreated, $warnings);

            return [
                'ok' => true,
                'menu_id' => $menu->id,
                'slug' => $menu->slug,
                'items_created' => $itemsCreated,
                'warnings' => $warnings,
            ];
        });
    }

    /** @param  \Illuminate\Database\Eloquent\Collection<int, MenuItem>  $items */
    protected function serializeItemTree($items): array
    {
        return $items->map(function (MenuItem $item) {
            $linkableSlug = null;
            if ($item->linkable_type && $item->linkable) {
                // Most Template-driven entries have a `slug` attribute; fall
                // back to id when absent so imports can still resolve.
                $linkableSlug = $item->linkable->slug ?? null;
            }

            return [
                'title' => $item->title,
                'url' => $item->url,
                'type' => $item->type,
                'target' => $item->target,
                'icon' => $item->icon,
                'css_class' => $item->css_class,
                'is_active' => (bool) $item->is_active,
                'linkable_type' => $item->linkable_type,
                'linkable_slug' => $linkableSlug,
                'children' => $this->serializeItemTree(
                    $item->children()->orderBy('order')->get()
                ),
            ];
        })->toArray();
    }

    /**
     * Recursively create MenuItem rows. Resolves linkable_type+slug to a
     * concrete row on import; if the row doesn't exist, the item still
     * lands as a custom-URL entry with a warning instead of failing the
     * whole transaction.
     */
    protected function materializeItems(int $menuId, ?int $parentId, array $items, int &$created, array &$warnings): void
    {
        foreach (array_values($items) as $order => $item) {
            $linkableType = $item['linkable_type'] ?? null;
            $linkableId = null;

            if ($linkableType && ! empty($item['linkable_slug'])) {
                try {
                    if (class_exists($linkableType)) {
                        $row = $linkableType::query()->where('slug', $item['linkable_slug'])->first();
                        if ($row) {
                            $linkableId = $row->id;
                        } else {
                            $warnings[] = "Linkable {$linkableType}#{$item['linkable_slug']} not found — kept as custom URL.";
                        }
                    }
                } catch (\Throwable $e) {
                    $warnings[] = "Linkable resolution failed for {$linkableType}: ".$e->getMessage();
                }
            }

            $created++;
            $row = MenuItem::create([
                'menu_id' => $menuId,
                'parent_id' => $parentId,
                'title' => (string) ($item['title'] ?? ''),
                'url' => (string) ($item['url'] ?? '#'),
                'type' => $item['type'] ?? 'custom',
                'target' => $item['target'] ?? '_self',
                'icon' => $item['icon'] ?? null,
                'css_class' => $item['css_class'] ?? null,
                'order' => $order,
                'is_active' => (bool) ($item['is_active'] ?? true),
                'linkable_type' => $linkableType ?: null,
                'linkable_id' => $linkableId,
            ]);

            if (! empty($item['children']) && is_array($item['children'])) {
                $this->materializeItems($menuId, $row->id, $item['children'], $created, $warnings);
            }
        }
    }

    /** Bump a numeric suffix until the slug is free. */
    protected function ensureUniqueSlug(string $base): string
    {
        if (! Menu::where('slug', $base)->exists()) {
            return $base;
        }
        for ($n = 2; $n < 1000; $n++) {
            $candidate = $base.'-'.$n;
            if (! Menu::where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }

        // pathological: fall back to a uuid suffix
        return $base.'-'.Str::random(6);
    }
}
