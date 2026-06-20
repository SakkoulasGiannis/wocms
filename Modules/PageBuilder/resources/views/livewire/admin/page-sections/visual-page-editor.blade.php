@push('scripts')
    @include('pagebuilder::partials.visual-page-editor._block-tools')
@endpush

<div class="flex h-screen overflow-hidden" style="height:100vh; position:relative;"
     x-data="{
         previewUrl: '{{ $previewUrl }}',
         iframeLoading: true,
         reloadPreview() {
             this.iframeLoading = true;
             const f = document.getElementById('preview-frame');
             if (!f) return;
             // Preserve the preview scroll position across the reload so an edit
             // doesn't jump the user back to the top of the page.
             let savedScroll = 0;
             try { savedScroll = (f.contentWindow && f.contentWindow.scrollY) || 0; } catch (e) {}
             const restore = () => {
                 try { f.contentWindow.scrollTo(0, savedScroll); } catch (e) {}
                 f.removeEventListener('load', restore);
             };
             f.addEventListener('load', restore);
             const url = new URL(f.src, location.href);
             url.searchParams.set('_t', Date.now());
             f.src = url.toString();
         }
     }"
     x-init="
         /* Warn before leaving with unsaved draft changes. $wire.isDirty stays
            in sync with the server-side draft flag. */
         window.addEventListener('beforeunload', (e) => {
             if ($wire.get('isDirty')) { e.preventDefault(); e.returnValue = ''; }
         });
     "
     x-on:preview-reload.window="reloadPreview()">

    {{-- Toast notifications --}}
    <div x-data="{ show: false, message: '', type: 'success', _timer: null }"
         x-on:notify.window="
             message = $event.detail.message;
             type = $event.detail.type ?? 'success';
             show = true;
             clearTimeout(_timer);
             _timer = setTimeout(() => show = false, 4000);
         "
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         style="position:fixed; bottom:24px; right:24px; z-index:9999;"
         :class="type === 'error' ? 'bg-red-600' : 'bg-gray-900'"
         class="flex items-center gap-2.5 px-4 py-3 rounded-lg shadow-xl text-white text-sm max-w-sm">
        <svg x-show="type === 'success'" class="w-4 h-4 flex-shrink-0 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg x-show="type === 'error'" class="w-4 h-4 flex-shrink-0 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span x-text="message"></span>
    </div>

    @include('pagebuilder::partials.visual-page-editor._sidebar')

    @include('pagebuilder::partials.visual-page-editor._edit-panel')
    @include('pagebuilder::partials.visual-page-editor._preview')
</div>

@push('styles')
    @include('pagebuilder::partials.visual-page-editor._styles')
@endpush

@push('scripts')
    @include('pagebuilder::partials.visual-page-editor._align-toolbar')
@endpush
