# weborange/visual-builder

A framework-agnostic, **build-step-free** visual HTML ↔ JSON page-section builder for
Laravel. Ships the whole editor (tree / live preview / inspector / component palette /
dynamic-token picker / media picker / dynamic-loop) as plain browser JS served straight
from the package — no npm, no Vite. The host app decides **where output is saved** and
**which dynamic sources/tokens exist** by implementing two small contracts.

## Install

```bash
composer require weborange/visual-builder
```

Auto-discovered. Visit `/visual-builder` (default prefix). The pure builder works
out of the box; saving and tokens stay disabled until you bind the contracts.

## Make it useful — implement the contracts

```php
use Weborange\VisualBuilder\Contracts\BuilderPersistence;
use Weborange\VisualBuilder\Contracts\TokenSource;

// In a service provider:
$this->app->bind(BuilderPersistence::class, MyPersistence::class);
$this->app->bind(TokenSource::class, MyTokenSource::class);
```

- **`BuilderPersistence`** — `targets()`, `sections($targetId)`, `save($payload)`.
  Decides where the builder HTML (or a loop config) is stored.
- **`TokenSource`** — `sources()`, `tokens($source)`. Exposes collections/entities and
  their `{field}` tokens for the token picker and dynamic loops.

## Configure

```bash
php artisan vendor:publish --tag=visual-builder-config
```

`config/visual-builder.php`: `prefix`, `middleware`, `as` (route-name prefix),
`layout` (Blade layout to extend), `content_section`, `title`, `media_url`
(optional Media-Library JSON endpoint returning `{items:[{url,thumb,name}]}`),
`register_routes` (set `false` to place the routes yourself, e.g. before a
catch-all). When disabled, register them inside your own group:

```php
Route::prefix('builder')->name('builder.')->group(function () {
    \Weborange\VisualBuilder\VisualBuilderServiceProvider::routes();
});
```

## JSON contract

Each node: `{ type, classes (string), attributes (object, excl. class),
content (direct text), children (array) }`. Editor-only fields (`_id`, `_name`,
`_collapsed`) are stripped on export.

See `app/VisualBuilder/Cms*` in the host CMS for a reference implementation.
