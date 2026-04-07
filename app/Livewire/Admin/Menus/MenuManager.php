<?php

namespace App\Livewire\Admin\Menus;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Template;
use Illuminate\Support\Str;
use Livewire\Component;

class MenuManager extends Component
{
    // Menu management
    public $menus;

    public ?int $selectedMenuId = null;

    public $menuItems = [];

    // Create/Edit menu modal
    public bool $showMenuModal = false;

    public ?int $editingMenuId = null;

    public string $menuName = '';

    public string $menuLocation = '';

    // Add item panel
    public string $addItemType = 'custom';

    public string $customTitle = '';

    public string $customUrl = '';

    public string $customTarget = '_self';

    // Template picker
    public $availableTemplates = [];

    public array $selectedTemplates = [];

    // Entry search
    public string $entrySearch = '';

    public array $searchResults = [];

    // Item editor
    public bool $showItemEditor = false;

    public ?int $editingItemId = null;

    public string $itemTitle = '';

    public string $itemUrl = '';

    public string $itemTarget = '_self';

    public string $itemCssClass = '';

    public function mount(): void
    {
        $this->loadMenus();
        $this->availableTemplates = Template::where('is_active', true)
            ->where('show_in_menu', true)
            ->orderBy('name')
            ->get();

        if ($this->menus->count() > 0) {
            $this->selectMenu($this->menus->first()->id);
        }
    }

    public function loadMenus(): void
    {
        $this->menus = Menu::orderBy('name')->get();
    }

    public function selectMenu(int $menuId): void
    {
        $this->selectedMenuId = $menuId;
        $this->loadMenuItems();
        $this->showItemEditor = false;
    }

    public function loadMenuItems(): void
    {
        if (! $this->selectedMenuId) {
            $this->menuItems = [];

            return;
        }

        $items = MenuItem::where('menu_id', $this->selectedMenuId)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->with(['children' => fn ($q) => $q->orderBy('order')
                ->with(['children' => fn ($q) => $q->orderBy('order')])])
            ->get();

        $this->menuItems = $this->itemsToArray($items);
    }

    protected function itemsToArray($items): array
    {
        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'type' => $item->type,
                'target' => $item->target,
                'css_class' => $item->css_class,
                'icon' => $item->icon,
                'is_active' => $item->is_active,
                'resolved_url' => $item->resolved_url,
                'children' => $this->itemsToArray($item->children),
            ];
        })->toArray();
    }

    // --- Menu CRUD ---

    public function openCreateMenu(): void
    {
        $this->reset(['editingMenuId', 'menuName', 'menuLocation']);
        $this->showMenuModal = true;
    }

    public function openEditMenu(): void
    {
        if (! $this->selectedMenuId) {
            return;
        }

        $menu = Menu::find($this->selectedMenuId);
        $this->editingMenuId = $menu->id;
        $this->menuName = $menu->name;
        $this->menuLocation = $menu->location ?? '';
        $this->showMenuModal = true;
    }

    public function saveMenu(): void
    {
        $this->validate([
            'menuName' => 'required|string|max:255',
            'menuLocation' => 'nullable|string|max:50',
        ]);

        $menu = Menu::updateOrCreate(
            ['id' => $this->editingMenuId],
            [
                'name' => $this->menuName,
                'slug' => Str::slug($this->menuName),
                'location' => $this->menuLocation ?: null,
            ]
        );

        $this->showMenuModal = false;
        $this->loadMenus();
        $this->selectMenu($menu->id);
        session()->flash('success', 'Menu saved.');
    }

    public function deleteMenu(): void
    {
        if (! $this->selectedMenuId) {
            return;
        }

        Menu::find($this->selectedMenuId)?->delete();
        $this->selectedMenuId = null;
        $this->menuItems = [];
        $this->loadMenus();
        if ($this->menus->count() > 0) {
            $this->selectMenu($this->menus->first()->id);
        }
        session()->flash('success', 'Menu deleted.');
    }

    // --- Add Items ---

    public function addHomepageItem(): void
    {
        if (! $this->selectedMenuId) {
            return;
        }

        MenuItem::create([
            'menu_id' => $this->selectedMenuId,
            'title' => 'Home',
            'url' => '/',
            'type' => 'homepage',
            'order' => MenuItem::where('menu_id', $this->selectedMenuId)->whereNull('parent_id')->count(),
        ]);
        $this->loadMenuItems();
    }

    public function addCustomItem(): void
    {
        if (! $this->selectedMenuId) {
            return;
        }

        $this->validate([
            'customTitle' => 'required|string|max:255',
            'customUrl' => 'required|string|max:255',
        ]);

        MenuItem::create([
            'menu_id' => $this->selectedMenuId,
            'title' => $this->customTitle,
            'url' => $this->customUrl,
            'type' => 'custom',
            'target' => $this->customTarget,
            'order' => MenuItem::where('menu_id', $this->selectedMenuId)->whereNull('parent_id')->count(),
        ]);

        $this->reset(['customTitle', 'customUrl', 'customTarget']);
        $this->customTarget = '_self';
        $this->loadMenuItems();
    }

    public function addTemplateItems(): void
    {
        if (! $this->selectedMenuId || empty($this->selectedTemplates)) {
            return;
        }

        $order = MenuItem::where('menu_id', $this->selectedMenuId)->whereNull('parent_id')->count();

        foreach ($this->selectedTemplates as $templateId) {
            $template = Template::find($templateId);
            if ($template) {
                MenuItem::create([
                    'menu_id' => $this->selectedMenuId,
                    'title' => $template->menu_label ?: $template->name,
                    'url' => '/'.$template->slug,
                    'type' => 'template',
                    'linkable_type' => Template::class,
                    'linkable_id' => $template->id,
                    'order' => $order++,
                ]);
            }
        }

        $this->selectedTemplates = [];
        $this->loadMenuItems();
    }

    // --- Search Entries ---

    public function updatedEntrySearch(): void
    {
        if (strlen($this->entrySearch) < 2) {
            $this->searchResults = [];

            return;
        }

        $results = [];
        $templates = Template::where('is_active', true)
            ->where('requires_database', true)
            ->get();

        foreach ($templates as $template) {
            $modelClass = str_contains($template->model_class, '\\')
                ? $template->model_class
                : "App\\Models\\{$template->model_class}";

            if (! class_exists($modelClass)) {
                continue;
            }

            try {
                $entries = $modelClass::where('title', 'like', "%{$this->entrySearch}%")
                    ->orWhere('name', 'like', "%{$this->entrySearch}%")
                    ->limit(5)
                    ->get();

                foreach ($entries as $entry) {
                    $title = $entry->title ?? $entry->name ?? 'Untitled';
                    $slug = $entry->slug ?? '';
                    $url = $template->use_slug_prefix
                        ? "/{$template->slug}/{$slug}"
                        : "/{$slug}";

                    $results[] = [
                        'title' => $title,
                        'url' => $url,
                        'type' => $template->name,
                        'template_slug' => $template->slug,
                        'entry_slug' => $slug,
                    ];
                }
            } catch (\Exception $e) {
                // Skip templates with missing tables
                continue;
            }
        }

        // Index for adding
        $this->searchResults = array_values(array_map(function ($r, $i) {
            $r['index'] = $i;

            return $r;
        }, $results, array_keys($results)));
    }

    public function addSearchResult(int $index): void
    {
        if (! $this->selectedMenuId || ! isset($this->searchResults[$index])) {
            return;
        }

        $result = $this->searchResults[$index];

        MenuItem::create([
            'menu_id' => $this->selectedMenuId,
            'title' => $result['title'],
            'url' => $result['url'],
            'type' => 'entry',
            'order' => MenuItem::where('menu_id', $this->selectedMenuId)->whereNull('parent_id')->count(),
        ]);

        $this->loadMenuItems();
        session()->flash('success', "Added '{$result['title']}' to menu.");
    }

    // --- Edit Item ---

    public function editItem(int $itemId): void
    {
        $item = MenuItem::find($itemId);
        if (! $item) {
            return;
        }

        $this->editingItemId = $item->id;
        $this->itemTitle = $item->title;
        $this->itemUrl = $item->url ?? '';
        $this->itemTarget = $item->target ?? '_self';
        $this->itemCssClass = $item->css_class ?? '';
        $this->showItemEditor = true;
    }

    public function updateItem(): void
    {
        $this->validate([
            'itemTitle' => 'required|string|max:255',
        ]);

        MenuItem::where('id', $this->editingItemId)->update([
            'title' => $this->itemTitle,
            'url' => $this->itemUrl ?: null,
            'target' => $this->itemTarget,
            'css_class' => $this->itemCssClass ?: null,
        ]);

        $this->showItemEditor = false;
        $this->loadMenuItems();
    }

    public function deleteItem(int $itemId): void
    {
        $item = MenuItem::find($itemId);
        if ($item) {
            // Delete children first
            $item->children()->delete();
            $item->delete();
        }
        $this->showItemEditor = false;
        $this->loadMenuItems();
    }

    public function toggleItemActive(int $itemId): void
    {
        $item = MenuItem::find($itemId);
        if ($item) {
            $item->update(['is_active' => ! $item->is_active]);
            $this->loadMenuItems();
        }
    }

    // --- Drag & Drop Reorder ---

    public function updateItemOrder(array $orderedItems): void
    {
        $this->saveOrderRecursive($orderedItems, null);
        $this->loadMenuItems();
    }

    protected function saveOrderRecursive(array $items, ?int $parentId): void
    {
        foreach ($items as $index => $item) {
            MenuItem::where('id', $item['id'])->update([
                'order' => $index,
                'parent_id' => $parentId,
            ]);

            if (! empty($item['children'])) {
                $this->saveOrderRecursive($item['children'], $item['id']);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.menus.menu-manager')
            ->layout('layouts.admin-clean')
            ->title('Menu Manager');
    }
}
