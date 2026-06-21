<?php

return [

    /*
     | Whether the package auto-registers its routes. Set false when the host
     | needs to place the routes itself (e.g. before a catch-all wildcard).
     */
    'register_routes' => true,

    /*
     | Route prefix for the builder UI and its JSON endpoints.
     */
    'prefix' => 'visual-builder',

    /*
     | Middleware applied to all builder routes. The host typically adds auth.
     */
    'middleware' => ['web'],

    /*
     | Route-name prefix (so the host can reverse-route the builder).
     */
    'as' => 'visual-builder.',

    /*
     | The Blade layout the builder view extends, and the section it injects
     | its content into. Override with the host app's admin layout.
     */
    'layout' => 'visual-builder::layout',
    'content_section' => 'content',

    /*
     | Page heading shown above the builder.
     */
    'title' => 'Visual Builder',

    /*
     | Extra stylesheet URLs injected into the PREVIEW iframe (in addition to the
     | chosen framework) so the canvas matches the live theme — e.g. the host's
     | compiled frontend CSS with its typography reset. Array of absolute/relative
     | URLs. Set from the host (e.g. Vite::asset('resources/css/frontend.css')).
     */
    'preview_css' => [],

];
