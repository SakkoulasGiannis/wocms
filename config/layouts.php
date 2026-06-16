<?php

/*
|--------------------------------------------------------------------------
| Frontend Layouts (page chrome)
|--------------------------------------------------------------------------
|
| Each entry maps a human-friendly slug to a Blade view name that
| ThemeManager::getLayout() can resolve (relative to the active theme,
| e.g. "layout" → themes.{theme}.layout, "layouts.minimal" →
| themes.{theme}.layouts.minimal).
|
| A ContentNode stores one of these slugs in its `layout` column. NULL
| means "inherit from the nearest ancestor, else the default below".
|
| IMPORTANT: a layout is fundamentally a Blade file. Adding an entry here
| without the matching Blade view will fall back to the default at render
| time (LayoutResolver guards against missing views). To add a real new
| layout: create the Blade file, then register it here.
|
*/

return [
    // The slug used when a node (and all its ancestors) has no layout set.
    'default' => 'default',

    // slug => [ label (admin dropdown), view (getLayout argument) ]
    'layouts' => [
        'default' => [
            'label' => 'Default (full header + footer)',
            'view' => 'layout',
        ],
        'minimal' => [
            'label' => 'Minimal (no header/footer)',
            'view' => 'layouts.minimal',
        ],
        'portal' => [
            'label' => 'Portal (own header + footer + menu)',
            'view' => 'layouts.portal',
        ],
        // Future:
        // 'landing' => ['label' => 'Landing (stripped)', 'view' => 'layouts.landing'],
    ],
];
