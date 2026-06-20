{{--
    Self-hosted EditorJS bundle — no CDN dependency.
    Files in /public/vendor/editorjs/ (downloaded once, cached forever via versioned path).
    Lazy loader: on the FIRST appearance of an .editorjs-container in the DOM, scripts
    are injected sequentially. Pages without an editor never pay the load cost.
--}}
<script>
@include('components.editorjs-field-parts._tools')
@include('components.editorjs-field-parts._factory')

/**
 * SINGLE source of truth for "read every editor's live content".
 * Every save button (entry form, visual editor X / fullscreen Save / Save &
 * Close) calls this instead of re-implementing the collection — that
 * divergence is what kept breaking saves. Returns one entry per mounted,
 * ready editor bound to a Livewire property (the hidden preload has no
 * wireModel and is skipped). Also cancels each editor's pending debounce and
 * runs the inline-formatting / alignment rescue passes.
 *
 * @returns {Promise<Array<{wireModel: string, json: string}>>}
 */
window.veCollectEditors = async function () {
    const out = [];
    const els = document.querySelectorAll('[x-data*="editorjsField"]');
    for (const el of els) {
        let data = null;
        try { data = window.Alpine && window.Alpine.$data(el); } catch (_) {}
        if (!data || !data.wireModel) { continue; }
        if (!data.editor || data.editor === '_loading_' || typeof data.editor.save !== 'function') { continue; }
        try {
            if (data._saveTimer) { clearTimeout(data._saveTimer); data._saveTimer = null; }
            const json = await data.editor.save();
            const root = document.getElementById(data.uid);
            if (typeof window.rescueInlineFormatting === 'function') { window.rescueInlineFormatting(json, root); }
            if (typeof window.patchAlignmentTunes === 'function') { window.patchAlignmentTunes(json, root); }
            out.push({ wireModel: data.wireModel, json: JSON.stringify(json) });
            try { data._autosaveClear && data._autosaveClear(); } catch (_) {}
        } catch (e) {
            console.warn('[veCollectEditors] save failed for', data.wireModel, e);
        }
    }
    return out;
};
</script>
