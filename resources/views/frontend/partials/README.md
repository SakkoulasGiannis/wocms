# Frontend Partials

This directory contains editable frontend components (header and footer).

## Files

### Editable Files (You can modify these)
- **header.blade.php** - The active header used in layout.blade.php
- **footer.blade.php** - The active footer used in layout.blade.php

### Default Templates (Do NOT edit these)
- **default-header.blade.php** - Default header template
- **default-footer.blade.php** - Default footer template

## How it works

1. **Edit freely**: You can edit `header.blade.php` and `footer.blade.php` as needed
2. **Fresh start resets**: When you run `php artisan fresh-start`, these files will be reset to their defaults
3. **Defaults preserved**: The `default-*.blade.php` files are templates that remain unchanged

## Usage in layout

The layout file includes these partials:
```blade
@include('frontend.partials.header')
@include('frontend.partials.footer')
```

## Custom Modifications

To make permanent changes to the default header/footer:
1. Edit `default-header.blade.php` or `default-footer.blade.php`
2. Run `php artisan fresh-start` to apply changes
