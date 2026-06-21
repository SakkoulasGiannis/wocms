# Installing the Visual Builder in another Laravel app

`weborange/visual-builder` is a **framework-agnostic, build-step-free** HTML ↔ JSON
page-section editor. The whole editor (tree / live preview / inspector / palette /
token & media & icon pickers / dynamic loops / forms / content WYSIWYG) ships as plain
browser JS served straight from the package — **no npm, no Vite**.

The host app only decides **where output is saved** and **what dynamic data exists**, by
implementing two small contracts. Everything else is provided.

Requirements: PHP ^8.2, Laravel 10/11/12.

---

## 1. Add the package

It is a path/local package (not on Packagist). Copy the `packages/weborange/visual-builder`
folder into the new project and add a path repository to the project `composer.json`:

```jsonc
"repositories": [
    { "type": "path", "url": "packages/weborange/visual-builder" }
],
"require": {
    "weborange/visual-builder": "*"
}
```

```bash
composer require weborange/visual-builder:*
composer dump-autoload
```

The service provider auto-discovers. Out of the box the **pure builder** works at
`/visual-builder`; saving + dynamic data stay disabled (Null* implementations) until you
bind the contracts below.

---

## 2. Implement the two contracts

### `BuilderPersistence` — where output lives

```php
namespace App\VisualBuilder;

use Weborange\VisualBuilder\Contracts\BuilderPersistence;

class MyPersistence implements BuilderPersistence
{
    /** Targets the user can save into. @return array<int,array{id:int|string,label:string,mode?:string,url?:?string}> */
    public function targets(): array { /* e.g. pages: ['id'=>1,'label'=>'Home — /','url'=>url('/')] */ }

    /** Existing builder sections of a target, for the Section dropdown / Load.
     *  @return array<int,array{id:int|string,name:string,html:string}> */
    public function sections(int|string $targetId): array { /* ... */ }

    /** HTML to pre-load when opening ?target=ID (migrate an existing page in). null = blank. */
    public function seedFor(int|string $targetId): ?string { /* return $page->body or rendered sections */ }

    /** Persist the builder output. Return ['success'=>bool,'message'=>string, 'url'?, 'edit_url'?,
     *  'section_id'?, 'needs_convert'?=>bool]. $payload keys:
     *   target_id, section_id (null=new), html (string), name, convert (bool),
     *   replace (bool, delete the target's existing sections first),
     *   loop (null | ['source','columns','limit','order_by','order_dir','heading']). */
    public function save(array $payload): array { /* ... */ }
}
```

### `TokenSource` — dynamic data for tokens / loops / forms

```php
namespace App\VisualBuilder;

use Weborange\VisualBuilder\Contracts\TokenSource;

class MyTokenSource implements TokenSource
{
    /** Collections/entities usable as loop sources. @return array<int,array{slug:string,name:string}> */
    public function sources(): array { /* ['slug'=>'blog','name'=>'Blog'] */ }

    /** {field} tokens for a source. @return array<int,array{token:string,label:string}> */
    public function tokens(string $source): array { /* ['token'=>'{title}','label'=>'title'] */ }

    /** Embeddable forms for the palette "Forms" group. @return array<int,array{slug:string,name:string}> */
    public function forms(): array { /* ['slug'=>'contact','name'=>'Contact'] — [] if none */ }

    /** Render a repeater's item template once per entity (live preview + frontend).
     *  $query: limit, order_by, order_dir, offset, filter_field, filter_value.
     *  @return array<int,string> resolved HTML per entity */
    public function renderLoop(string $source, array $query, string $itemHtml): array { /* ... */ }
}
```

### Bind them (host service provider)

```php
public function register(): void
{
    $this->app->bind(\Weborange\VisualBuilder\Contracts\BuilderPersistence::class, \App\VisualBuilder\MyPersistence::class);
    $this->app->bind(\Weborange\VisualBuilder\Contracts\TokenSource::class, \App\VisualBuilder\MyTokenSource::class);

    config([
        'visual-builder.as'              => 'admin.visual-builder.',
        'visual-builder.layout'          => 'layouts.admin',     // your admin layout
        'visual-builder.content_section' => 'content',           // @section name it injects into
        'visual-builder.title'           => 'Page Builder',
        'visual-builder.media_url'       => url('admin/media'),   // optional, see §5
        'visual-builder.upload_url'      => url('admin/upload'),  // optional
    ]);
}

public function boot(): void
{
    // optional: load your theme CSS into the preview so WYSIWYG matches live (§6)
    try { config(['visual-builder.preview_css' => [\Illuminate\Support\Facades\Vite::asset('resources/css/app.css')]]); }
    catch (\Throwable $e) {}
}
```

---

## 3. Routes & where to mount the UI

`config/visual-builder.php` keys: `register_routes` (default `true`), `prefix`,
`middleware`, `as`, `layout`, `content_section`, `title`, `media_url`, `upload_url`,
`preview_css`.

Default: auto-registered at `/visual-builder`. To place them yourself (e.g. add `auth`
middleware, or sit **before** a catch-all `{slug}` route), set `register_routes => false`
and register inside your own group:

```php
// routes/web.php — inside your admin group, BEFORE any {wildcard}
Route::prefix('admin/page-builder')->name('admin.visual-builder.')->middleware(['web','auth'])
    ->group(fn () => \Weborange\VisualBuilder\VisualBuilderServiceProvider::routes());
```

Routes provided: `index` (GET, the UI; accepts `?target=ID` to edit a page in place),
`save` (POST), `sections` (GET), `tokens` (GET), `sample` (POST, live loop preview),
`asset/{file}.js` (GET, serves the JS engine — cache-busted by filemtime, no publish).

Link to it from your admin with `route('admin.visual-builder.index')` or
`route('admin.visual-builder.index', ['target' => $page->id])` to edit existing content.

---

## 4. Render the saved HTML on the frontend (host side)

`save()` stores plain HTML. Two builder constructs need host-side expansion when you
render that HTML publicly:

- `data-vb-loop='{json query}'` on an element → repeat its first child per entity.
- `data-vb-form="slug"` on an element → replace with your live form component.

The package does **not** render your frontend — copy `app/VisualBuilder/LoopRenderer.php`
from the reference CMS (DOMDocument: find `[data-vb-loop]` → `TokenSource::renderLoop`,
find `[data-vb-form]` → your form component) and run section HTML through it:

```blade
{!! app(\App\VisualBuilder\LoopRenderer::class)->expandHtml($section->html) !!}
```

If you don't use loops/forms, you can echo the saved HTML directly.

---

## 5. Media picker (optional)

Set `media_url` to a JSON endpoint returning `{items:[{url,thumb,name}]}` and `upload_url`
to an endpoint that accepts a multipart `image` field and returns `{file:{url}}`. The
inspector's "Pick" button and the palette image upload use them. Omit to hide media UI.

---

## 6. Preview accuracy (optional)

`preview_css` is an array of stylesheet URLs injected into the preview iframe (on top of
the chosen CSS framework) so the canvas matches your live theme — e.g. your compiled
frontend CSS with its typography defaults. Set it from the host (see §2 `boot`).

---

## 7. JSON / HTML contract

Each node:

```jsonc
{
  "type": "p",                       // tag
  "classes": "text-lg font-bold",    // class string
  "attributes": { "href": "#" },     // everything except class
  "content": "plain text",           // direct text (escaped on output)
  "html": "Hi <strong>there</strong>",  // OPTIONAL rich inline HTML (content WYSIWYG: bold/colour/link).
                                         // When present it is emitted verbatim and wins over `content`.
  "children": [ /* nested nodes */ ]
}
```

- Editor-only fields `_id`, `_name`, `_collapsed` are stripped on export.
- Repeater = any node with attribute `data-vb-loop`; its first child is the per-item
  template using `{token}` placeholders.
- Form = a node with attribute `data-vb-form="slug"`.

---

## 8. Modifying the editor (no build step)

All editor logic is plain browser JS in `resources/js/new-builder/*.js`, served by the
`asset` route and cache-busted automatically by file mtime — **edit a file, reload, done**
(no npm/Vite). Key modules:

- `palette.js` — `GROUPS` array = the "Insert block" library; add an entry to add a block.
  A dynamic "Forms" group is built from `TokenSource::forms()`.
- `inspector.js` — `CONTROL_GROUPS` = the segmented "Quick styles" (align/weight/size/…);
  `CHIP`/colour swatches + the content mini-WYSIWYG (bold/italic/underline/colour/link).
- `builder-core.js` — the HTML↔JSON serializer/parser (`jsonToHtml`, `htmlToJsonRoots`,
  `INLINE_FORMAT_TAGS` for rich `html`). `roundtrip-test.cjs` guards the round-trip:
  `node resources/js/new-builder/roundtrip-test.cjs`.
- `preview.js` — the live iframe; `save.js` — the save panel; `tokens.js` / `media.js` /
  `icons.js` / `elements.js` / `codemodal.js` — pickers and editors.
- `builder.blade.php` (resources/views) — toolbar, panels, and all CSS for the UI.

Server side is thin: `src/Http/Controllers/BuilderController.php` only validates and
delegates to your contracts; add behaviour there or in your contract implementations.

---

## Reference implementation

This CMS ships a complete host integration to copy from:
`app/VisualBuilder/CmsBuilderPersistence.php`, `CmsTokenSource.php`, `LoopRenderer.php`,
and `app/Providers/VisualBuilderHostServiceProvider.php`.
