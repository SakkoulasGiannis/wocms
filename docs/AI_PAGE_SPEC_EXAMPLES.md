# AI Page Spec — JSON Examples

Feed these examples to any AI (Claude / ChatGPT / Gemini) together with a brief like:

> *"Build me a page about X. Output the full JSON spec following the same schema as the example below."*

The output goes straight into `php artisan page:compile path/to/file.json` or
through the AI Page Builder UI.

---

## Tech stack & styling conventions (READ THIS FIRST)

Tell the AI explicitly:

- **Backend:** Laravel 12 + Livewire 3 + Volt. Sections render server-side via
  Blade components in `resources/views/components/sections/`.
- **CSS:** **Tailwind CSS v4** — CSS-first config (no `tailwind.config.js`),
  theme tokens declared in `resources/css/frontend.css` with `@theme { … }`.
- **No v3 deprecated utilities.** Use replacements:
  `bg-opacity-50` → `bg-black/50`, `flex-shrink-0` → `shrink-0`,
  `overflow-ellipsis` → `text-ellipsis`, etc.
- **Tailwind Play CDN is loaded** on every admin / visual-editor page, so
  arbitrary classes (e.g. `lg:grid-cols-5`, `bg-white/3`, `xl:gap-x-20`,
  `aspect-[4/3]`) work out of the box.
- **Frontend is responsive-first.** Always emit `sm:`/`md:`/`lg:`/`xl:` variants.
- **Language:** Greek-first content for the kecms / KretaEiendom site, English
  for kept-generic sites. Match the source request.

### Brand tokens to prefer over raw Tailwind colors

The theme exposes semantic color tokens that should be used in place of
generic `text-blue-600`, `bg-gray-50`, etc. The site's identity stays
consistent if the AI uses these:

| Token | What it means | Hex |
|---|---|---|
| `text-brand` / `bg-brand` | Primary brand color | `#1563df` |
| `bg-brand-hover` / `text-brand-hover` | Brand on hover | `#0e49a6` |
| `bg-brand-soft` / `text-brand-soft` | Brand tint background | `#f3f7fd` |
| `text-on-surface` | Default body / heading color | `#161e2d` |
| `bg-surface` | Section card backgrounds | `#f7f7f7` |
| `border-outline` / `ring-outline` | Subtle borders / rings | `#e4e4e4` |
| `text-variant-1` | Secondary copy | `#5c6368` |
| `text-variant-2` | Muted labels / icons | `#a3abb0` |
| `text-critical` / `bg-critical` | Errors, destructive | `#c72929` |
| `text-success` / `bg-success` | Success / confirmation | `#198754` |
| `text-yellow` / `bg-yellow` | Highlights / warnings | `#ffa800` |
| `shadow-card` / `shadow-soft` / `shadow-strong` | Theme shadows | — |
| `font-sans` | Manrope (default) | — |
| `max-w-8xl` | 88rem container | — |

### Composition patterns the theme uses

When the AI writes `wysiwyg` HTML (in `liveHtml` / `raw` / `card_html` blocks),
prefer these patterns rather than ad-hoc choices:

- Section wrapper: `py-16 lg:py-24 bg-white` (or `bg-surface` for tinted)
- Container: `mx-auto max-w-7xl px-4 sm:px-6 lg:px-8` (or `max-w-8xl`)
- Card: `rounded-2xl bg-white shadow-card ring-1 ring-outline`
- Card hover: `transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30`
- Heading: `text-3xl font-extrabold capitalize text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]`
- Subhead: `text-sm font-semibold uppercase tracking-[0.2em] text-brand`
- Body copy: `text-variant-1`
- Primary CTA: `inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-hover`

---

## Schema at a glance

```jsonc
{
  "type": "page",
  "page": {
    "title": "string",
    "slug": "string-with-dashes",          // unique, no leading slash
    "status": "draft|published",
    "render_mode": "sections|html|markdown", // 'sections' = use the sections[] tree
    "featured_image": null,
    "body": null,                           // only used when render_mode='html'
    "body_css": null,
    "seo": {
      "title": "string|null",
      "description": "string|null",
      "keywords": "string|null",
      "focus_keyword": "string|null"
    }
  },
  "sections": [
    {
      "section_type": "wysiwyg|hero-simple|features-grid|...",
      "name": "Human-readable label",
      "order": 0,
      "scope": "entry",                 // 'entry' (default) or 'listing'
      "is_active": true,
      "is_visible": true,
      "edit_mode": "form|wysiwyg",      // 'wysiwyg' opens the EditorJS pane
      "content": { /* shape depends on section_type — see below */ },
      "settings": { /* optional per-section settings */ },
      "css": null,                      // raw CSS, scoped to this section — last resort
      "children": [ /* nested sections, same schema */ ]
    }
  ]
}
```

### Available `section_type` slugs

Built-in section templates ship in the seeded library. The AI may pick any of:

| Slug | What it renders |
|---|---|
| `wysiwyg` | EditorJS rich-content block (see below) |
| `hero-simple` | Headline + subhead + CTA + bg-image hero |
| `hero-slider` / `hero-slider-home5` | Multi-slide hero |
| `features-grid` | 3- or 4-column icon-text grid |
| `benefits` | Two-column feature list w/ icons |
| `services-grid` | Card-grid of services |
| `service-card` | Single highlighted service card |
| `about-us` | Image + text introduction block |
| `call-to-action` | Wide CTA banner |
| `testimonials` / `testimonials-carousel` | Quotes block |
| `agents-grid` | Team / agent cards |
| `featured-property` | Single property highlight |
| `property-search` | Embedded search form |
| `explore-cities` | City tiles |
| `entry-loop` | Loop ANY template's entries through token-bound cards |
| `blog-loop` | Specialized loop for blog posts |
| `gallery` | Image gallery |
| `contact-form` | Embedded contact form |
| `faq-accordion` | Q&A accordion |
| `stats-counter` | Animated stat counters |
| `trusted-partners` | Logo strip |
| `custom-html` | Raw HTML block |
| `structured-html` | JSON-tree → HTML (Tailwind classes) |

### Class / styling fields you can pass into sections

Most form-based sections accept Tailwind class strings in their `content`
fields — these flow straight into the rendered Blade component without
sanitization, so any valid Tailwind utility works:

| Where | Field | What it styles |
|---|---|---|
| Most sections | `content.section_class` | The outer `<section>` wrapper |
| Most sections | `content.container_class` | The inner centered container |
| Hero variants | `content.overlay_class` | Image overlay (e.g. `bg-black/40`) |
| Hero variants | `content.heading_class` / `content.subheading_class` | Override title typography |
| Card / CTA blocks | `content.background_class` | Section background tint |
| `entry-loop` / `sectionEmbed` | `content.section_class` | Loop section wrapper |
| `entry-loop` | `content.card_image_fallback` | Fallback image URL when entry has none |
| Any section | `css` (top-level) | Raw scoped CSS — last resort, prefer classes |
| Any block (EditorJS) | `tunes.blockClasses.classes` | Appends Tailwind classes to the block's primary element |
| `container` block | `data.wrapperClass` / `data.innerClass` | Outer + inner div classes |
| `image` block | `tunes.imageSize.size` / `tunes.imageSize.custom` | Width override (`25` / `50` / `75` / `100` / `custom`) |
| Page-level | `page.body_css` | Raw page CSS injected at top — last resort |

**Rule of thumb:** when in doubt about where to apply a class, use the
EditorJS `blockClasses` tune. It works on EVERY block type and is the
preferred mechanism for the visual editor too.

### The `wysiwyg` field accepts raw Tailwind HTML — paste anywhere

Three ways to put **arbitrary Tailwind-styled HTML** inside a wysiwyg
section:

1. **`liveHtml` block** — preferred. Renders the HTML verbatim AND keeps
   it editable in the editor (you can click any element to edit its
   classes via the Style picker, and use Source mode to edit raw HTML).
   Paste-detection inside the editor auto-routes any pasted styled HTML
   (anything with `class=`/`style=`) into a single `liveHtml` block, so
   the AI can output rich Tailwind markup and the user can still tweak it
   without losing classes.

   ```jsonc
   {
     "type": "liveHtml",
     "data": {
       "html": "<section class=\"py-20 bg-brand-soft\"><div class=\"mx-auto max-w-7xl px-4\"><h2 class=\"text-3xl font-extrabold text-on-surface md:text-4xl\">Title</h2></div></section>"
     }
   }
   ```

2. **`raw` block** — same renderer, but no inline edit affordances.
   Use for snippets the user shouldn't accidentally modify (embed scripts,
   SVG illustrations, third-party widgets).

   ```jsonc
   { "type": "raw", "data": { "html": "<svg viewBox=\"0 0 24 24\">…</svg>" } }
   ```

3. **`structured-html` section** (whole section, not a block) — a JSON
   tree of `{type, classes, children}` that the renderer compiles to HTML.
   Best for AI-driven layouts where you want every node accessible to
   downstream tooling without parsing strings.

**Important:** the admin layouts load **Tailwind Play CDN**, so even
arbitrary utilities not in the compiled `app.css` (`lg:grid-cols-5`,
`bg-white/3`, `xl:gap-x-20`, `aspect-[4/3]`, custom values via
`[whatever]`) render correctly in the editor preview.

### The `wysiwyg` content shape

`wysiwyg` sections carry an EditorJS save in `content.value`:

```jsonc
{
  "section_type": "wysiwyg",
  "content": {
    "value": {
      "time": 1717488000000,
      "version": "2.30.7",
      "blocks": [
        { "id": "abc123", "type": "header",    "data": { "text": "Title", "level": 2 } },
        { "id": "abc124", "type": "paragraph", "data": { "text": "Body copy." } }
      ]
    }
  }
}
```

### Available EditorJS block types

| `type` | `data` shape | Notes |
|---|---|---|
| `header` | `{ text, level: 1..6 }` | |
| `paragraph` | `{ text }` | Inline HTML allowed (`<b>`, `<i>`, `<a>`, marker, color spans) |
| `list` | `{ style: "unordered"\|"ordered", items: [{ content, items: [] }] }` | Nested |
| `nestedList` | Same as `list` | Alias |
| `quote` | `{ text, caption, alignment }` | |
| `code` | `{ code, language }` | |
| `delimiter` | `{}` | Horizontal rule |
| `image` | `{ file: { url }, caption, withBorder, withBackground, stretched }` | |
| `embed` | `{ service, source, embed, width, height, caption }` | YouTube/Vimeo/etc. |
| `table` | `{ withHeadings: true, content: [["cell", "cell"], ...] }` | |
| `raw` | `{ html }` | Verbatim HTML |
| `liveHtml` | `{ html }` | Same as raw, but editable inline with class picker |
| `container` | `{ desktop: "7xl", tablet: "full", mobile: "full", wrapperClass, innerClass, content: { blocks: [...] } }` | Responsive max-width wrapper |
| `columns` | `{ cols: 2..6, columns: [{ blocks: [...] }, ...] }` | Nested EditorJS in each column |
| `space` | `{ height: "2rem"\|"40px"\|"5vh" }` | Vertical spacer |
| `sectionEmbed` | `{ source_template, limit, columns, gap, order_by, order_dir, heading, subheading, section_class, card_template_slug, card_html }` | Loop ANY template's entries through a card design |

### Block tunes (optional, added to a block)

```jsonc
{
  "type": "paragraph",
  "data": { "text": "Centered" },
  "tunes": {
    "textAlignment": { "alignment": "center" },         // left|center|right|justify
    "blockClasses":  { "classes": "py-4 bg-amber-50" } // Tailwind classes appended to primary element
  }
}
```

### Token substitution in `entry-loop` / `sectionEmbed`

When looping entries, card HTML can use these placeholders:

- `{title}`, `{slug}`, `{description}` — direct entry fields
- `{description:raw}` — wysiwyg / markdown fields, rendered as HTML (no escape)
- `{main_image:preview}`, `{cover:thumb}` — Spatie media URLs at conversion size
- `{entry_url}` — `/template-slug/entry-slug`
- `{field|fallback}` — default value if field is empty

---

## Example 1 — Home page (full-fat)

A hero + features + 3-up entry-loop of rentals + CTA + FAQ + footer-CTA combo.

```json
{
  "type": "page",
  "page": {
    "title": "KretaEiendom — Your home in Crete",
    "slug": "home",
    "status": "published",
    "render_mode": "sections",
    "featured_image": null,
    "body": null,
    "body_css": null,
    "seo": {
      "title": "KretaEiendom · Real Estate & Holiday Rentals in Crete",
      "description": "Browse villas, apartments and rental properties across Crete — from Chania to Sitia.",
      "keywords": "crete, real estate, villas, holiday rentals, kretaeiendom"
    }
  },
  "sections": [
    {
      "section_type": "hero-simple",
      "name": "Hero",
      "order": 0,
      "scope": "entry",
      "is_active": true,
      "is_visible": true,
      "edit_mode": "form",
      "content": {
        "heading": "Find your place in Crete",
        "subheading": "Curated villas, sea-view apartments and short-term stays — vetted by locals.",
        "primary_cta_label": "Browse properties",
        "primary_cta_url": "/properties",
        "secondary_cta_label": "Rentals",
        "secondary_cta_url": "/rental-properties",
        "background_image": "/themes/kretaeiendom/images/hero-crete.jpg",
        "overlay_class": "bg-black/40"
      },
      "settings": null,
      "css": null
    },
    {
      "section_type": "features-grid",
      "name": "Why us",
      "order": 1,
      "scope": "entry",
      "is_active": true,
      "is_visible": true,
      "edit_mode": "form",
      "content": {
        "heading": "Why KretaEiendom",
        "subheading": "Three reasons people keep coming back.",
        "columns": 3,
        "items": [
          { "icon": "shield", "title": "Vetted listings",    "description": "Every property is visited by our team." },
          { "icon": "location", "title": "Local expertise",  "description": "Born in Crete — we know every village." },
          { "icon": "phone", "title": "Direct contact",      "description": "No middlemen. Reach owners in one click." }
        ]
      },
      "settings": null,
      "css": null
    },
    {
      "section_type": "wysiwyg",
      "name": "Featured rentals — loop",
      "order": 2,
      "scope": "entry",
      "is_active": true,
      "is_visible": true,
      "edit_mode": "wysiwyg",
      "content": {
        "value": {
          "time": 1717488000000,
          "version": "2.30.7",
          "blocks": [
            {
              "id": "loop1",
              "type": "sectionEmbed",
              "data": {
                "source_template": "rental-properties",
                "limit": 6,
                "columns": 3,
                "gap": "normal",
                "order_by": "created_at",
                "order_dir": "desc",
                "heading": "Featured rentals",
                "subheading": "Latest from our portfolio",
                "section_class": "py-16 bg-white",
                "card_template_slug": "classic",
                "card_html": ""
              }
            }
          ]
        }
      },
      "settings": null,
      "css": null
    },
    {
      "section_type": "call-to-action",
      "name": "Owner CTA",
      "order": 3,
      "scope": "entry",
      "is_active": true,
      "is_visible": true,
      "edit_mode": "form",
      "content": {
        "heading": "Own a property in Crete?",
        "subheading": "List it with us — we handle photography, vetting and bookings.",
        "cta_label": "List your property",
        "cta_url": "/contact?subject=listing",
        "background_class": "bg-brand"
      },
      "settings": null,
      "css": null
    },
    {
      "section_type": "faq-accordion",
      "name": "FAQ",
      "order": 4,
      "scope": "entry",
      "is_active": true,
      "is_visible": true,
      "edit_mode": "form",
      "content": {
        "heading": "Frequently asked",
        "items": [
          { "question": "Do you handle bookings?",       "answer": "Yes — for rental properties, we manage the entire booking flow." },
          { "question": "Can foreigners buy in Crete?",  "answer": "Yes. We guide you through the residency and tax process." },
          { "question": "What's the average commission?", "answer": "Sale commissions start at 3%, paid only on close." }
        ]
      },
      "settings": null,
      "css": null
    }
  ]
}
```

---

## Example 2 — Internal page (rich wysiwyg)

A typical content page that's mostly text + media, with one inline section embed
at the bottom. Notice the variety of EditorJS blocks.

```json
{
  "type": "page",
  "page": {
    "title": "About Crete",
    "slug": "about-crete",
    "status": "published",
    "render_mode": "sections",
    "featured_image": null,
    "body": null,
    "body_css": null,
    "seo": {
      "title": "About Crete — Greece's largest island",
      "description": "Geography, climate and culture of Crete — a complete primer for visitors and would-be residents.",
      "keywords": "crete, greece, island, mediterranean"
    }
  },
  "sections": [
    {
      "section_type": "wysiwyg",
      "name": "Article body",
      "order": 0,
      "scope": "entry",
      "is_active": true,
      "is_visible": true,
      "edit_mode": "wysiwyg",
      "content": {
        "value": {
          "time": 1717488000000,
          "version": "2.30.7",
          "blocks": [
            { "id": "h1",  "type": "header",    "data": { "text": "About Crete",            "level": 1 } },
            { "id": "p1",  "type": "paragraph", "data": { "text": "Crete is the largest of the Greek islands and the fifth largest in the Mediterranean Sea." } },
            { "id": "img1","type": "image",     "data": { "file": { "url": "/themes/kretaeiendom/images/crete-map.jpg" }, "caption": "The island of Crete", "withBorder": false, "withBackground": false, "stretched": true } },

            { "id": "h2",  "type": "header",    "data": { "text": "Geography",              "level": 2 } },
            { "id": "p2",  "type": "paragraph", "data": { "text": "Crete spans <b>260 km</b> from east to west, dominated by the White Mountains, Mount Ida and Dikti ranges." } },
            { "id": "l1",  "type": "list",      "data": { "style": "unordered", "items": [
                { "content": "<b>North coast</b> — calmer seas, busier resorts",   "items": [] },
                { "content": "<b>South coast</b> — wilder, dramatic cliffs",       "items": [] },
                { "content": "<b>Interior</b> — gorges, villages, olive groves",   "items": [] }
            ] } },

            { "id": "h3",  "type": "header",    "data": { "text": "Climate", "level": 2 } },
            { "id": "p3",  "type": "paragraph", "data": { "text": "Hot dry summers, mild wet winters. Tourist season runs late April through October." } },
            { "id": "q1",  "type": "quote",     "data": { "text": "Crete is a continent unto itself.", "caption": "Nikos Kazantzakis", "alignment": "left" } },

            { "id": "d1",  "type": "delimiter", "data": {} },

            { "id": "h4",  "type": "header",    "data": { "text": "Browse properties in Crete", "level": 2 } },
            {
              "id": "loop1",
              "type": "sectionEmbed",
              "data": {
                "source_template": "completed-villas",
                "limit": 3,
                "columns": 3,
                "gap": "normal",
                "order_by": "created_at",
                "order_dir": "desc",
                "heading": "",
                "subheading": "",
                "section_class": "py-8",
                "card_template_slug": "wide",
                "card_html": ""
              }
            }
          ]
        }
      },
      "settings": null,
      "css": null
    }
  ]
}
```

---

## Example 3 — Same idea but minimal (skeleton for the AI to fill)

If you want to feed the AI a tiny stub and let it expand, this is the smallest
valid spec:

```json
{
  "type": "page",
  "page": {
    "title": "<<AI fills>>",
    "slug": "<<AI fills, kebab-case>>",
    "status": "draft",
    "render_mode": "sections"
  },
  "sections": [
    {
      "section_type": "wysiwyg",
      "name": "Body",
      "order": 0,
      "edit_mode": "wysiwyg",
      "content": {
        "value": {
          "time": 1717488000000,
          "version": "2.30.7",
          "blocks": [
            { "type": "header",    "data": { "text": "<<AI: page heading>>", "level": 1 } },
            { "type": "paragraph", "data": { "text": "<<AI: 1–2 sentence intro>>" } }
          ]
        }
      }
    }
  ]
}
```

---

## Prompt template

Paste this on top when asking an AI:

> You are generating a CMS page spec for a **Laravel 12 + Livewire 3** site
> styled with **Tailwind CSS v4**. The Tailwind Play CDN is loaded on the
> editor pages, so any arbitrary utility (responsive variants, fractional
> opacity, aspect ratios) works.
>
> Reply with **valid JSON only**, no markdown fences, no commentary.
>
> ### Constraints
> - Match the schema exactly — use only the listed `section_type` slugs,
>   never invent new ones.
> - For text-heavy sections use `wysiwyg` with EditorJS blocks. For dynamic
>   listings (rentals, properties, blog posts) use a `sectionEmbed` block
>   inside a wysiwyg section with `card_template_slug` ∈ {`classic`,
>   `wide`, `overlay`}.
> - `status` must be `"draft"` on first generation. `render_mode` must be
>   `"sections"`.
> - All Tailwind classes you emit must be **Tailwind v4** — no deprecated
>   v3 utilities (`bg-opacity-*`, `flex-shrink-*`, `overflow-ellipsis`).
> - Prefer the project's **semantic brand tokens** over raw color
>   utilities:
>     `text-brand` / `bg-brand` / `bg-brand-hover` / `bg-brand-soft`,
>     `text-on-surface`, `bg-surface`, `border-outline` / `ring-outline`,
>     `text-variant-1` / `text-variant-2`,
>     `text-critical` / `text-success` / `text-yellow`,
>     `shadow-card` / `shadow-soft` / `shadow-strong`,
>     `max-w-8xl`, `font-sans` (Manrope).
> - Be responsive — emit `sm:` / `md:` / `lg:` / `xl:` variants.
> - For per-section styling use the listed class fields (`section_class`,
>   `container_class`, `overlay_class`, `background_class`, …) — these are
>   plain Tailwind class strings, no sanitization.
> - `wysiwyg` sections **accept full Tailwind HTML inside** via
>   `liveHtml` (preferred, editable) or `raw` (verbatim) blocks. Feel
>   free to emit `<section class="…">…</section>` chunks with arbitrary
>   Tailwind v4 utilities — the editor preserves them and the Play CDN
>   resolves any class on the fly.
> - Language: match the user's input language (Greek-first for the
>   KretaEiendom / kecms site).
>
> Now build me: **[describe your page here]**
>
> Example:
> ```json
> <<paste Example 1 or 2 here>>
> ```
