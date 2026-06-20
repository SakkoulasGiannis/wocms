/**
 * new-builder media picker: a modal that lists images from the Media Library
 * (GET admin/editorjs/media -> { items: [{id,name,url,thumb}] }) and returns
 * the chosen image URL via a callback. Reused by the inspector to set an
 * <img> src.
 *
 * NB.createMediaPicker(rootEl) sets rootEl.__nbMedia = { open(cb) }.
 */
(function () {
    'use strict';

    var NB = window.NB;

    function escapeAttr(v) {
        return String(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    NB.createMediaPicker = function createMediaPicker(rootEl) {
        var cfg = rootEl.querySelector('[data-media-config]');
        if (!cfg) { return; }
        var endpoint = cfg.getAttribute('data-media-url');
        var uploadUrl = cfg.getAttribute('data-upload-url');
        var csrf = cfg.getAttribute('data-csrf');
        var cb = null;
        var debounce = null;

        var overlay = document.createElement('div');
        overlay.className = 'nb-media-overlay hidden';
        overlay.innerHTML =
            '<div class="nb-media-modal">' +
            '<div class="nb-media-head">' +
            '<strong>Media Library</strong>' +
            '<input type="search" class="nb-media-search" placeholder="Search images…">' +
            (uploadUrl
                ? '<button type="button" class="nb-media-upload" data-media-upload>&#8593; Upload</button>' +
                  '<input type="file" accept="image/*" data-media-file class="hidden">'
                : '') +
            '<button type="button" class="nb-media-close" title="Close">&times;</button>' +
            '</div>' +
            '<div class="nb-media-grid" data-media-grid></div>' +
            '<div class="nb-media-status" data-media-status></div>' +
            '</div>';
        rootEl.appendChild(overlay);

        var grid = overlay.querySelector('[data-media-grid]');
        var status = overlay.querySelector('[data-media-status]');
        var search = overlay.querySelector('.nb-media-search');
        var fileInput = overlay.querySelector('[data-media-file]');

        function uploadFile(file) {
            if (!uploadUrl || !file) { return; }
            status.textContent = 'Uploading…';
            var fd = new FormData();
            fd.append('image', file);
            fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: fd,
            }).then(function (r) { return r.json(); }).then(function (j) {
                var url = j && j.file && j.file.url ? j.file.url : (j && j.url);
                if (url) {
                    var fn = cb;
                    close();
                    if (typeof fn === 'function') { fn(url); }
                } else {
                    status.textContent = 'Upload failed.';
                }
            }).catch(function (e) { status.textContent = 'Upload error: ' + e.message; });
        }

        function close() {
            overlay.classList.add('hidden');
            cb = null;
        }

        function open(callback) {
            cb = callback;
            overlay.classList.remove('hidden');
            search.value = '';
            load('');
            search.focus();
        }

        function load(q) {
            status.textContent = 'Loading…';
            grid.innerHTML = '';
            var u = endpoint + (q ? ('?q=' + encodeURIComponent(q)) : '');
            fetch(u, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    var items = (j && j.items) || [];
                    if (!items.length) { status.textContent = 'No images found.'; return; }
                    status.textContent = '';
                    grid.innerHTML = items.map(function (m) {
                        return '<button type="button" class="nb-media-item" ' +
                            'data-media-pick="' + escapeAttr(m.url) + '" title="' + escapeAttr(m.name || '') + '">' +
                            '<img src="' + escapeAttr(m.thumb || m.url) + '" alt="" loading="lazy"></button>';
                    }).join('');
                })
                .catch(function (e) { status.textContent = 'Error: ' + e.message; });
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.closest('.nb-media-close')) {
                close();
                return;
            }
            if (e.target.closest('[data-media-upload]')) {
                if (fileInput) { fileInput.click(); }
                return;
            }
            var pick = e.target.closest('[data-media-pick]');
            if (pick) {
                var url = pick.getAttribute('data-media-pick');
                var fn = cb;
                close();
                if (typeof fn === 'function') { fn(url); }
            }
        });

        search.addEventListener('input', function () {
            clearTimeout(debounce);
            var q = search.value.trim();
            debounce = setTimeout(function () { load(q); }, 300);
        });

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files[0]) { uploadFile(fileInput.files[0]); }
                fileInput.value = '';
            });
        }

        rootEl.__nbMedia = { open: open };
    };
}());
