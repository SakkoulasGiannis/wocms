{{--
    SectionEmbed data provider. Renders no UI of its own — the inline editor
    lives INSIDE the EditorJS block placeholder (see SectionEmbedTool in
    _tools.blade.php). Earlier iterations tried a layout-level Livewire/Alpine
    modal but it conflicted with the fullscreen wysiwyg overlay (opening the
    modal collapsed the fullscreen). Going fully inline avoids that entirely.

    All this component does is dump the templates list, card library and token
    catalog as a `window.SE_DATA` JSON blob so the tool can populate its
    dropdowns client-side. Mounted once per admin page from the layout.
--}}
<div>
    <script>
        // Idempotent — re-mounts (wire:navigate, Livewire morph) refresh the
        // payload without leaking listeners or duplicate definitions.
        window.SE_DATA = {
            templates: @js($templates),
            cards: @js($cards),
            tokensByTemplate: @js($tokensByTemplate),
        };
        // Tell any already-mounted SectionEmbed inline editors that fresh
        // data is available, so a newly-saved library card shows up without
        // a page reload.
        window.dispatchEvent(new CustomEvent('kecms-section-embed:data-ready'));
    </script>
</div>
