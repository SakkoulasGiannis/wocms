# Bootstrap 5 Theme

A professional, responsive theme built with Bootstrap 5.

## Installation

1. Download Bootstrap 5 CSS and JS:
   - Visit https://getbootstrap.com/docs/5.3/getting-started/download/
   - Download the compiled CSS and JS files

2. Place the files in:
   - `assets/css/bootstrap.min.css`
   - `assets/js/bootstrap.bundle.min.js`

3. (Optional) Create custom styles in `assets/css/custom.css`

4. Run the symlink command:
   ```bash
   php artisan theme:link
   ```

5. Activate the theme from Admin Panel → Settings → Active Theme → bootstrap

## Quick Start (CDN)

If you don't want to download Bootstrap, you can use CDN instead. Edit `theme.json`:

```json
{
  "assets": {
    "css": [
      "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    ],
    "js": [
      "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
    ]
  }
}
```

## Customization

- **Layout**: Edit `layout.blade.php`
- **Header**: Edit `partials/header.blade.php`
- **Footer**: Edit `partials/footer.blade.php`
- **Custom CSS**: Add to `assets/css/custom.css`

## Features

- Responsive navigation with mobile menu
- Bootstrap components ready to use
- Livewire compatible
- SEO optimized
