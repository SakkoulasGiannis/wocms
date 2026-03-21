# WOCMS Theming System

## Overview

The CMS uses a database-driven theming system built around the `ThemeManager` service. Themes live in `resources/views/themes/{slug}/` and are switched from Admin Settings. The active theme is stored in the `settings` table (`active_theme` key) with a 24-hour cache.

---

## Architecture

```
ThemeManager (singleton)
    ├── getActiveTheme()     → reads from Setting model (cached 24h)
    ├── getLayout()          → resolves layout with fallback chain
    ├── getPartial()         → resolves partials (header, footer, sidebar)
    ├── getTemplateView()    → resolves content templates
    ├── renderCssAssets()    → renders <link> tags from theme.json
    ├── renderJsAssets()     → renders <script> tags from theme.json
    ├── usesVite()           → checks if theme uses Vite bundling
    └── clearCache()         → invalidates all theme caches
```

### Key Files

| File | Purpose |
|------|---------|
| `app/Services/ThemeManager.php` | Core theming service |
| `app/Console/Commands/ThemeLinkCommand.php` | `php artisan theme:link` command |
| `app/Models/Setting.php` | Settings storage (active_theme) |
| `app/Livewire/Admin/Settings/SettingsPage.php` | Admin theme selector |
| `app/Http/Controllers/FrontendController.php` | Frontend rendering with theme |
| `app/Providers/AppServiceProvider.php` | ThemeManager singleton registration |

---

## Theme Directory Structure

```
resources/views/themes/{theme-slug}/
├── layout.blade.php              # Main page layout
├── partials/
│   ├── header.blade.php          # Site header/navigation
│   ├── footer.blade.php          # Site footer
│   └── sidebar.blade.php         # Optional sidebar
├── templates/                    # Content-type view overrides
│   ├── default.blade.php
│   ├── simple.blade.php
│   └── sections.blade.php
├── assets/
│   ├── css/
│   └── js/
└── theme.json                    # Theme metadata & config
```

### theme.json

```json
{
  "name": "Theme Display Name",
  "slug": "theme-slug",
  "description": "Theme description",
  "version": "1.0.0",
  "author": "Author Name",
  "screenshot": "screenshot.png",
  "supports": {
    "layouts": ["layout", "layout-full-width"],
    "partials": ["header", "footer", "sidebar"],
    "css_framework": "bootstrap|tailwindcss"
  },
  "assets": {
    "css": ["/themes/theme-slug/assets/css/style.css"],
    "js": ["/themes/theme-slug/assets/js/app.js"]
  },
  "vite": false
}
```

Asset URLs support: relative paths, absolute paths, and CDN URLs.

---

## Included Themes

| Theme | Slug | CSS Framework | Asset Loading |
|-------|------|---------------|---------------|
| Bootstrap | `bootstrap` | Bootstrap 5 | Traditional (CSS/JS links) |
| Tailwind | `tailwind` | Tailwind CSS 4 | Vite bundling |

**Default theme**: `tailwind`

---

## View Resolution (Fallback Chain)

Each view type follows a 3-level fallback:

**Layouts** (`ThemeManager::getLayout()`):
1. `themes.{active_theme}.layout`
2. `themes.tailwind.layout`
3. `frontend.layout`

**Partials** (`ThemeManager::getPartial()`):
1. `themes.{active_theme}.partials.{name}`
2. `themes.tailwind.partials.{name}`
3. `frontend.partials.{name}`

**Templates** (`ThemeManager::getTemplateView()`):
1. `themes.{active_theme}.templates.{template}`
2. `themes.tailwind.templates.{template}`
3. `frontend.{template}`

---

## Asset Symlinks

Theme assets need to be publicly accessible. The `theme:link` command creates symlinks:

```bash
php artisan theme:link [--force]
```

Creates: `public/themes/{slug}/assets/` → `resources/views/themes/{slug}/assets/`

---

## Layout Template Pattern

```blade
<head>
    @if(Setting::get('site_favicon'))
        <link rel="shortcut icon" href="{{ Setting::get('site_favicon') }}">
    @endif

    <x-seo-meta :entry="$content ?? $post ?? null" />

    @php $themeManager = app(\App\Services\ThemeManager::class); @endphp

    @if($themeManager->usesVite())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {!! $themeManager->renderCssAssets() !!}
    @endif

    @livewireStyles
    @stack('styles')
</head>
<body>
    @include($themeManager->getPartial('header'))
    <main>@yield('content')</main>
    @include($themeManager->getPartial('footer'))

    @if(!$themeManager->usesVite())
        {!! $themeManager->renderJsAssets() !!}
    @endif

    @livewireScripts
    @stack('scripts')
</body>
```

---

## Settings Used in Themes

Partials can access these via `Setting::get()`:

| Key | Used In |
|-----|---------|
| `site_name` | Header, Footer |
| `site_logo` | Header |
| `site_favicon` | Layout `<head>` |
| `site_description` | Footer |
| `site_phone`, `site_email`, `site_address` | Footer |
| `social_facebook`, `social_instagram`, etc. | Footer |

---

## Theme Switching

1. Admin goes to `/admin/settings` → General tab
2. Selects theme from dropdown (populated by `ThemeManager::getAvailableThemes()`)
3. On save: `Setting::set('active_theme', $slug)` + `ThemeManager::clearCache()`
4. Next page load uses the new theme

### Cache Invalidation

On theme switch, these caches are cleared:
- `active_theme`
- `theme_metadata.{slug}`
- `settings.group.general`
- `setting.active_theme`

---

## Creating a New Theme

1. Create directory: `resources/views/themes/{new-slug}/`
2. Add `theme.json` with metadata
3. Add `layout.blade.php` (main layout)
4. Add `partials/header.blade.php` and `partials/footer.blade.php`
5. Optionally add `templates/` overrides and `assets/`
6. Run `php artisan theme:link`
7. Select the theme in Admin Settings

Any missing view automatically falls back to the Tailwind theme, then to generic `frontend/` views.
