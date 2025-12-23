# Module Menu Integration Guide

## Overview
This CMS supports dynamic menu items from Laravel Modules. Each module can register its own menu items that will automatically appear in the admin navigation when the module is enabled.

## How It Works

1. **Module Status**: Only **enabled** modules will have their menus displayed
2. **Configuration File**: Each module needs a `Config/menu.php` file
3. **Dynamic Loading**: The `MenuService` automatically loads and merges module menus with the static menu

## Creating a Module with Menu

### Step 1: Create a New Module

```bash
php artisan module:make Blog
```

### Step 2: Create Menu Configuration

Create a file at `Modules/Blog/Config/menu.php`:

```php
<?php

return [
    /**
     * Show this module in the admin menu
     * Set to false to hide the module from menu even if enabled
     */
    'show_in_menu' => true,

    /**
     * Section name in the menu
     * Groups multiple menu items together
     */
    'section' => 'Blog Management',

    /**
     * Menu items for this module
     */
    'items' => [
        [
            'label' => 'All Posts',
            'icon' => 'newspaper',  // Font Awesome icon (without 'fa-' prefix)
            'route' => 'admin.blog.posts.index'
        ],
        [
            'label' => 'Categories',
            'icon' => 'folder',
            'route' => 'admin.blog.categories.index'
        ],
        [
            'label' => 'Tags',
            'icon' => 'tags',
            'route' => 'admin.blog.tags.index'
        ]
    ]
];
```

### Step 3: Define Routes

In `Modules/Blog/Routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/blog')->name('admin.blog.')->middleware(['web', 'auth'])->group(function () {
    Route::resource('posts', PostController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('tags', TagController::class);
});
```

### Step 4: Enable the Module

```bash
php artisan module:enable Blog
```

The module menu will now appear in the admin navigation!

### Step 5: Disable the Module (Optional)

```bash
php artisan module:disable Blog
```

The module menu will automatically disappear from the navigation.

## Menu Configuration Options

### Required Fields

- **`show_in_menu`** (boolean): Set to `true` to display in menu
- **`items`** (array): Array of menu items

### Optional Fields

- **`section`** (string): Group name for the menu items (defaults to module name)
- **`icon`** (string): Font Awesome icon for each menu item
- **`route`** (string): Laravel route name for the menu link

## Menu Item Structure

Each menu item can have:

```php
[
    'label' => 'Display Name',           // Required: Text shown in menu
    'icon' => 'icon-name',               // Optional: FA icon (without 'fa-' prefix)
    'route' => 'route.name',             // Required: Laravel route name
    'badge' => '5',                      // Optional: Badge to show (e.g., notification count)
    'badge_color' => 'red'               // Optional: Badge color (red, blue, green, yellow)
]
```

## Example: E-commerce Module

`Modules/Shop/Config/menu.php`:

```php
<?php

return [
    'show_in_menu' => true,
    'section' => 'E-commerce',
    'items' => [
        [
            'label' => 'Products',
            'icon' => 'box',
            'route' => 'admin.shop.products.index'
        ],
        [
            'label' => 'Orders',
            'icon' => 'shopping-cart',
            'route' => 'admin.shop.orders.index',
            'badge' => '3',              // Show 3 pending orders
            'badge_color' => 'red'
        ],
        [
            'label' => 'Customers',
            'icon' => 'users',
            'route' => 'admin.shop.customers.index'
        ],
        [
            'label' => 'Settings',
            'icon' => 'cog',
            'route' => 'admin.shop.settings'
        ]
    ]
];
```

## Checking Module Menu Status Programmatically

```php
use App\Services\MenuService;

$menuService = app(MenuService::class);

// Check if a module's menu should be displayed
$shouldDisplay = $menuService->shouldDisplayModuleMenu('Blog');

// Get all admin menu items (static + modules)
$fullMenu = $menuService->getAdminMenu();
```

## Troubleshooting

### Menu Not Appearing

1. **Check module is enabled**: `php artisan module:list`
2. **Verify menu config exists**: `Modules/YourModule/Config/menu.php`
3. **Check `show_in_menu` is true**: Open the config file
4. **Clear cache**: `php artisan cache:clear && php artisan view:clear`

### Route Not Found Error

1. **Verify route is defined** in module's `Routes/web.php`
2. **Check route name matches** the one in menu config
3. **Run** `php artisan route:list | grep "your-route-name"`

## Best Practices

1. ✅ **Use descriptive section names** to group related items
2. ✅ **Choose clear, concise labels** for menu items
3. ✅ **Use appropriate Font Awesome icons** that match the functionality
4. ✅ **Test module enable/disable** to ensure menu appears/disappears
5. ✅ **Keep menu items relevant** - don't clutter the navigation
6. ✅ **Use badges sparingly** - only for important notifications

## Static Menu

The static menu is defined in `config/schemas/menu.json` and contains core CMS items like Dashboard, Templates, etc. Module menus are appended after the static menu.

To modify the static menu, edit: `/config/schemas/menu.json`

## Advanced: Conditional Menu Items

You can make menu items conditional by checking permissions or settings:

```php
<?php

return [
    'show_in_menu' => true,
    'section' => 'Advanced',
    'items' => array_filter([
        [
            'label' => 'Analytics',
            'icon' => 'chart-line',
            'route' => 'admin.analytics.index'
        ],
        // Only show if user has permission
        auth()->user()->can('manage-settings') ? [
            'label' => 'Settings',
            'icon' => 'cog',
            'route' => 'admin.settings.index'
        ] : null,
    ])
];
```

---

**Need help?** Check the example modules or consult the main documentation.
