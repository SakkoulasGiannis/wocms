# Example Blog Module

This is an example module to demonstrate the dynamic menu system in the CMS.

## Testing the Module Menu

### 1. Check Module Status

```bash
php artisan module:list
```

You should see `ExampleBlog` in the list with status `Disabled`.

### 2. Enable the Module

```bash
php artisan module:enable ExampleBlog
```

**Expected Result**: The menu item "Blog Management" with sub-items should now appear in the admin navigation.

### 3. View the Admin Menu

Visit: `http://your-app-url/admin/dashboard`

You should see a new section "Blog Management" in the top navigation with:
- 📰 All Posts
- 📁 Categories
- 🏷️ Tags

### 4. Disable the Module

```bash
php artisan module:disable ExampleBlog
```

**Expected Result**: The "Blog Management" menu should disappear from the navigation.

### 5. Test show_in_menu Flag

Edit `Config/menu.php` and change:

```php
'show_in_menu' => false,
```

Then enable the module again:

```bash
php artisan module:enable ExampleBlog
```

**Expected Result**: Even though the module is enabled, the menu won't appear because `show_in_menu` is false.

## Menu Configuration

The menu is defined in `/Config/menu.php`. You can customize:

- **Section name**: Change `'section' => 'Blog Management'`
- **Menu items**: Add/remove items in the `items` array
- **Icons**: Change Font Awesome icons (without `fa-` prefix)
- **Routes**: Update route names to match your actual routes

## Creating Actual Routes

To make this a real module, you would need to create:

1. **Routes**: `Routes/web.php`
2. **Controllers**: `Http/Controllers/PostController.php`, etc.
3. **Views**: `Resources/views/posts/index.blade.php`, etc.
4. **Models**: `Entities/Post.php`, `Entities/Category.php`, etc.

But for testing the menu system, this basic structure is enough!

## Customization

Feel free to modify this example or create your own modules following the same pattern.

For full documentation, see: `/docs/MODULE_MENU_GUIDE.md`
